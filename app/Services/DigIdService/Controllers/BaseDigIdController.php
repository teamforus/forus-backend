<?php

namespace App\Services\DigIdService\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DigID\CompleteDigIdRequest;
use App\Models\Identity;
use App\Services\DigIdService\DigIdServiceLogger;
use App\Services\DigIdService\Models\DigIdSession;
use App\Services\DigIdService\Objects\DigidAuthRequestData;
use App\Services\SAML2Service\Responses\SamlArtifactResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

abstract class BaseDigIdController extends Controller
{
    /**
     * @param DigIdSession $session
     * @param DigidAuthRequestData|null $authRequest
     * @return JsonResponse
     */
    protected function makeStartResponse(DigIdSession $session, ?DigidAuthRequestData $authRequest): JsonResponse
    {
        if (!$authRequest || !$session->isPending()) {
            abort(503, 'Unable to handle the request at the moment.', [
                'Error-Code' => 'digid_' . $session->getErrorKey(),
            ]);
        }

        return new JsonResponse([
            'session_uid' => $session->session_uid,
            ...($session->isConnectionTypeTvs() ? [
                'type' => 'post',
                'destination' => $authRequest->getMeta('destination'),
                'fields' => [
                    'SAMLRequest' => $authRequest->getMeta('saml_request'),
                    'RelayState' => $session->session_uid,
                ],
            ] : [
                'type' => 'redirect',
                'redirect_url' => $authRequest->getAuthRedirectUrl(),
            ]),
        ], 200, ['Cache-Control' => 'no-store']);
    }

    /**
     * @param DigIdSession $session
     * @param Request|SamlArtifactResponse $response
     * @throws Throwable
     * @return RedirectResponse
     */
    protected function resolveSession(
        DigIdSession $session,
        Request|SamlArtifactResponse $response,
    ): RedirectResponse {
        return DB::transaction(function () use ($session, $response) {
            $session = $session->newQuery()->lockForUpdate()->findOrFail($session->id);

            if (!$session->isPending() || $session->isExpired() || empty($session->meta['browser_challenge'])) {
                return $this->makeRedirectErrorResponse($session, 'unknown_error');
            }

            $session->resolveResponse($response);

            if (!$session->isAuthorized()) {
                return $this->makeRedirectErrorResponse($session, $session->getErrorKey());
            }

            if ($session->isExpired()) {
                return $this->makeRedirectErrorResponse($session, 'unknown_error');
            }

            $completionCode = bin2hex(random_bytes(32));

            $session->update([
                'meta' => [
                    ...$session->meta,
                    'completion_code_hash' => hash('sha256', $completionCode),
                    'completion_consumed' => false,
                ],
            ]);

            return redirect($session->implementation->urlWebshop('/digid-complete', [
                'transport' => $session->getTransport(),
                'session_uid' => $session->session_uid,
                'completion_code' => $completionCode,
            ]))->withHeaders([
                'Cache-Control' => 'no-store',
                'Referrer-Policy' => 'no-referrer',
            ]);
        });
    }

    /**
     * @param CompleteDigIdRequest $request
     * @param string $transport
     * @throws Throwable
     * @return JsonResponse
     */
    protected function completeSession(CompleteDigIdRequest $request, string $transport): JsonResponse
    {
        $result = DB::transaction(function () use ($request, $transport) {
            $session = DigIdSession::query()
                ->where('session_uid', $request->input('session_uid'))
                ->lockForUpdate()
                ->first();

            if (!$session) {
                $this->rejectCompletion('session_not_found');
            }

            if ($session->getTransport() !== $transport) {
                $this->rejectCompletion('transport_mismatch', $session->id);
            }

            if (!$session->isAuthorized()) {
                $this->rejectCompletion('session_not_authorized', $session->id);
            }

            if ($session->isExpired()) {
                $this->rejectCompletion('session_expired', $session->id);
            }

            if (!empty($session->meta['completion_consumed'])) {
                $this->rejectCompletion('completion_consumed', $session->id);
            }

            if ($request->implementation()?->id !== $session->implementation_id) {
                $this->rejectCompletion('implementation_mismatch', $session->id);
            }

            if ($request->client_type() !== $session->client_type) {
                $this->rejectCompletion('client_type_mismatch', $session->id);
            }

            if (!hash_equals(
                $session->meta['browser_challenge'] ?? '',
                hash('sha256', $request->input('browser_verifier')),
            )) {
                $this->rejectCompletion('invalid_browser_verifier', $session->id);
            }

            if (!hash_equals(
                $session->meta['completion_code_hash'] ?? '',
                hash('sha256', $request->input('completion_code')),
            )) {
                $this->rejectCompletion('invalid_completion_code', $session->id);
            }

            if ($session->isSessionRequestFundRequest() && (
                !$request->isAuthenticated() || $request->auth_address() !== $session->identity_address
            )) {
                $this->rejectCompletion('identity_mismatch', $session->id);
            }

            if (!$session->sessionOrganization()) {
                $this->rejectCompletion('organization_not_found', $session->id);
            }

            $response = match (true) {
                $session->isSessionRequestAuth() => $this->_resolveAuth($session, $request),
                $session->isSessionRequestFundRequest() => $this->_resolveFundRequest($session),
                default => $this->makeRedirectErrorResponse($session, 'unknown_error'),
            };

            $session->update([
                'meta' => [
                    ...$session->meta,
                    'completion_code_hash' => null,
                    'browser_challenge' => null,
                    'completion_consumed' => true,
                ],
            ]);

            return ['redirect_url' => $response->getTargetUrl()];
        });

        return new JsonResponse($result, 200, ['Cache-Control' => 'no-store']);
    }

    /**
     * @param string $reason
     * @param int|null $sessionId
     * @return never
     */
    protected function rejectCompletion(string $reason, ?int $sessionId = null): never
    {
        DigIdServiceLogger::logError('Could not complete DigiD authentication.', context: [
            'reason' => $reason,
            'session_id' => $sessionId,
        ]);

        abort(403, 'Unable to complete DigiD authentication.', ['Error-Code' => 'digid_unknown_error']);
    }

    /**
     * @param DigIdSession $session
     * @param array $data
     * @param string|null $url
     * @return RedirectResponse
     */
    protected function makeRedirectResponse(DigIdSession $session, array $data, ?string $url = null): RedirectResponse
    {
        return redirect(url_extend_get_params($url ?: $session->session_final_url, $data));
    }

    /**
     * @param DigIdSession $session
     * @param string $error
     * @param string|null $url
     * @return RedirectResponse
     */
    protected function makeRedirectErrorResponse(
        DigIdSession $session,
        string $error,
        ?string $url = null,
    ): RedirectResponse {
        return $this->makeRedirectResponse($session, ['digid_error' => $error], $url);
    }

    /**
     * @param DigIdSession $session
     * @return RedirectResponse|bool
     */
    protected function handleBsnAssign(DigIdSession $session): RedirectResponse|bool
    {
        $digidBsn = $session->digidBsn();
        $digidBsnIdentity = $session->digidBsnIdentity();
        $sessionIdentity = $session->sessionIdentity();
        $sessionIdentityBsn = $session->sessionIdentityBsn();

        // Identity already has a bsn attached, and it's different
        if ($sessionIdentityBsn && $sessionIdentityBsn !== $digidBsn) {
            return $this->makeRedirectErrorResponse($session, 'uid_dont_match');
        }

        // The digid bsn is already in the system but belongs to someone else
        if ($digidBsnIdentity && $digidBsnIdentity->address !== $sessionIdentity->address) {
            return $this->makeRedirectErrorResponse($session, 'uid_used');
        }

        // The session organization have bsn_enabled and
        if ($session->sessionOrganization()->bsn_enabled) {
            return (bool) $session->identity->setBsnRecord($session->digid_uid);
        }

        return false;
    }

    /**
     * @param DigIdSession $session
     * @param CompleteDigIdRequest $request
     * @return RedirectResponse
     */
    protected function _resolveAuth(DigIdSession $session, CompleteDigIdRequest $request): RedirectResponse
    {
        $identity = $session->digidBsnIdentity();

        if (!$identity) {
            if (!$session->implementation->digid_sign_up_allowed) {
                return $this->makeRedirectErrorResponse($session, 'uid_not_found');
            }

            $identity = Identity::build();
        }

        $proxy = Identity::makeAuthorizationShortTokenProxy();
        $identity->activateAuthorizationShortTokenProxy($proxy->exchange_token, $request->ip());

        $session->setIdentity($identity);
        $assignResult = $this->handleBsnAssign($session);

        // Redirect with an error
        if ($assignResult instanceof RedirectResponse) {
            return $assignResult;
        }

        return $this->makeRedirectResponse($session, [
            'token' => $proxy->exchange_token,
        ], sprintf('%s/auth-link', rtrim($session->session_final_url, '/')));
    }

    /**
     * @param DigIdSession $session
     * @return RedirectResponse
     */
    protected function _resolveFundRequest(DigIdSession $session): RedirectResponse
    {
        $assignResult = $this->handleBsnAssign($session);

        // Redirect with an error
        if ($assignResult instanceof RedirectResponse) {
            return $assignResult;
        }

        return $this->makeRedirectResponse($session, array_merge([
            'digid_success' => $assignResult ? 'signed_up' : 'signed_in',
        ]));
    }
}
