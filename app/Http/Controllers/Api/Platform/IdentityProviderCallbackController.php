<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderErrorCode;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderException;
use App\Services\IdentityProviderService\Models\IdentityProviderAdminSession;
use App\Services\IdentityProviderService\Responses\IdentityProviderRedirectResponse;
use App\Services\IdentityProviderService\Services\IdentityProviderConnectionService;
use App\Services\IdentityProviderService\Services\IdentityProviderLogService;
use App\Services\IdentityProviderService\Services\IdentityProviderOidcService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class IdentityProviderCallbackController extends Controller
{
    /**
     * @param IdentityProviderLogService $logService
     * @param IdentityProviderOidcService $oidcService
     * @param IdentityProviderConnectionService $connectionService
     */
    public function __construct(
        protected IdentityProviderLogService $logService,
        protected IdentityProviderOidcService $oidcService,
        protected IdentityProviderConnectionService $connectionService,
    ) {
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function oidcCallback(Request $request): RedirectResponse
    {
        $state = $request->query('state');
        $adminSession = $this->connectionService->findAdminSessionByState($state);

        return $adminSession
            ? $this->tenantVerificationCallback($adminSession, $request)
            : $this->accountAuthenticationCallback($request);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function adminConsentCallback(Request $request): RedirectResponse
    {
        $session = $this->connectionService->findAdminConsentSession($request->query('state'));

        if (!$session) {
            $diagnosticId = $this->logService->protocolFailure(
                IdentityProviderLogService::OPERATION_ADMIN_CONSENT_CALLBACK,
                IdentityProviderErrorCode::ADMIN_CONSENT_SESSION_NOT_FOUND,
                [
                    'state_present' => is_string($request->query('state')) && $request->query('state') !== '',
                    'provider_error_code' => $request->query('error'),
                ],
            );

            return IdentityProviderRedirectResponse::fallback(IdentityProviderErrorCode::ADMIN_CONSENT_SESSION_NOT_FOUND, $diagnosticId);
        }

        try {
            $session = $this->connectionService->resolveAdminConsent($session, $request);

            return IdentityProviderRedirectResponse::forAdminSession($session, []);
        } catch (Throwable $exception) {
            $errorCode = $exception instanceof IdentityProviderException
                ? $exception->errorCode()
                : IdentityProviderErrorCode::ADMIN_CONSENT_CALLBACK_FAILED;

            $diagnosticId = $this->logService->exceptionFailure(
                IdentityProviderLogService::OPERATION_ADMIN_CONSENT_CALLBACK,
                $exception,
                IdentityProviderLogService::adminSessionContext($session),
                $errorCode,
            );

            return IdentityProviderRedirectResponse::forAdminSession(
                $session,
                IdentityProviderRedirectResponse::failureParams($errorCode, $diagnosticId),
            );
        }
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    protected function accountAuthenticationCallback(Request $request): RedirectResponse
    {
        $state = $request->query('state');
        $session = $this->oidcService->findByState($state);

        if (!$session) {
            $diagnosticId = $this->logService->protocolFailure(
                IdentityProviderLogService::OPERATION_OIDC_CALLBACK,
                IdentityProviderErrorCode::ENTRA_SESSION_NOT_FOUND,
                ['state_present' => is_string($state) && $state !== ''],
            );

            return IdentityProviderRedirectResponse::fallback(IdentityProviderErrorCode::ENTRA_SESSION_NOT_FOUND, $diagnosticId);
        }

        try {
            return new IdentityProviderRedirectResponse($this->oidcService->resolveCallback($session, $request));
        } catch (Throwable $exception) {
            $errorCode = $exception instanceof IdentityProviderException
                ? $exception->errorCode()
                : IdentityProviderErrorCode::CALLBACK_FAILED;

            $diagnosticId = $this->logService->exceptionFailure(
                IdentityProviderLogService::OPERATION_OIDC_CALLBACK,
                $exception,
                IdentityProviderLogService::sessionContext($session),
                $errorCode,
            );

            return IdentityProviderRedirectResponse::forSessionFailure(
                $session,
                $errorCode,
                $diagnosticId,
            );
        }
    }

    /**
     * @param IdentityProviderAdminSession $session
     * @param Request $request
     * @return RedirectResponse
     */
    protected function tenantVerificationCallback(
        IdentityProviderAdminSession $session,
        Request $request,
    ): RedirectResponse {
        try {
            $result = $this->connectionService->resolveAdminTenant($session, $request);

            return new IdentityProviderRedirectResponse($result['redirect_url']);
        } catch (Throwable $exception) {
            $errorCode = $exception instanceof IdentityProviderException
                ? $exception->errorCode()
                : IdentityProviderErrorCode::TENANT_VERIFICATION_FAILED;

            $diagnosticId = $this->logService->exceptionFailure(
                IdentityProviderLogService::OPERATION_TENANT_VERIFICATION_CALLBACK,
                $exception,
                IdentityProviderLogService::adminSessionContext($session),
                $errorCode,
            );

            return IdentityProviderRedirectResponse::forAdminSession(
                $session,
                IdentityProviderRedirectResponse::failureParams($errorCode, $diagnosticId),
            );
        }
    }
}
