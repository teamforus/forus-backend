<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\IdentityProviders\ExchangeIdentityProviderLoginRequest;
use App\Http\Requests\Api\Platform\IdentityProviders\StartIdentityProviderLoginRequest;
use App\Models\Implementation;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderErrorCode;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderException;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderHttpFailure;
use App\Services\IdentityProviderService\Models\IdentityProviderOidcSession;
use App\Services\IdentityProviderService\Responses\IdentityProviderOidcStartResponse;
use App\Services\IdentityProviderService\Services\IdentityProviderLogService;
use App\Services\IdentityProviderService\Services\IdentityProviderOidcService;
use Illuminate\Http\JsonResponse;
use Throwable;

class IdentityProviderLoginController extends Controller
{
    /**
     * @param IdentityProviderLogService $logService
     * @param IdentityProviderOidcService $oidcService
     */
    public function __construct(
        protected IdentityProviderLogService $logService,
        protected IdentityProviderOidcService $oidcService,
    ) {
    }

    /**
     * @param StartIdentityProviderLoginRequest $request
     * @return JsonResponse
     */
    public function login(StartIdentityProviderLoginRequest $request): JsonResponse
    {
        $mode = match ($request->client_type()) {
            Implementation::FRONTEND_SPONSOR_DASHBOARD => IdentityProviderOidcSession::MODE_DASHBOARD,
            Implementation::FRONTEND_WEBSHOP => IdentityProviderOidcSession::MODE_WEBSHOP,
        };

        try {
            $target = $request->string('target')->toString() ?: null;
            $result = $this->oidcService->startLogin($request->implementation(), $mode, $target);

            return new IdentityProviderOidcStartResponse($result['session'], $result['browser_token']);
        } catch (Throwable $exception) {
            throw IdentityProviderHttpFailure::toException(
                $exception,
                operation: IdentityProviderLogService::OPERATION_OIDC_START,
                context: ['mode' => $mode],
                fallbackCode: IdentityProviderErrorCode::OIDC_START_FAILED,
                fallbackMessage: __('exceptions.identity_providers.oidc_start_failed'),
            );
        }
    }

    /**
     * @param ExchangeIdentityProviderLoginRequest $request
     * @return JsonResponse
     */
    public function exchange(ExchangeIdentityProviderLoginRequest $request): JsonResponse
    {
        try {
            $proxy = $this->oidcService->exchange(
                $request->string('exchange_token')->toString(),
                $request->string('browser_token')->toString(),
                (string) $request->ip(),
            );

            return new JsonResponse([
                'access_token' => $proxy->access_token,
                'organization_id' => $proxy->identity_provider_binding?->connection?->organization_id,
            ]);
        } catch (IdentityProviderException) {
            abort(403, 'The Entra login exchange is invalid or expired.');
        } catch (Throwable $exception) {
            $diagnosticId = $this->logService->exceptionFailure(
                IdentityProviderLogService::OPERATION_LOGIN_EXCHANGE,
                $exception,
                errorCode: IdentityProviderErrorCode::LOGIN_EXCHANGE_FAILED,
            );

            return new JsonResponse([
                'error_code' => IdentityProviderErrorCode::LOGIN_EXCHANGE_FAILED,
                'message' => __('exceptions.identity_providers.login_exchange_failed'),
                'diagnostic_id' => $diagnosticId,
            ], 503);
        }
    }
}
