<?php

namespace App\Http\Controllers;

use App\Http\Requests\DigID\ResolveDigIdRequest;
use App\Http\Requests\DigID\StartDigIdRequest;
use App\Services\DigIdService\Models\DigIdSession;
use App\Models\Identity;
use Illuminate\Http\RedirectResponse;

class DigIdController extends Controller
{
    /**
     * @param StartDigIdRequest $request
     * @return array
     * @throws \Throwable
     */
    public function start(StartDigIdRequest $request): array
    {
        $digidSession = DigIdSession::createSession($request)->startAuthSession();

        if (!$digidSession->isPending()) {
            abort(503, 'Unable to handle the request at the moment.', [
                'Error-Code' => strtolower('digid_' . $digidSession->digid_error_code),
            ]);
        }

        return [
            'redirect_url' => $digidSession->getRedirectUrl(),
        ];
    }

    /**
     * @param DigIdSession $session
     * @return RedirectResponse
     */
    public function redirect(DigIdSession $session): RedirectResponse
    {
        return redirect($session->digid_auth_redirect_url);
    }

    /**
     * @param ResolveDigIdRequest $request
     * @param DigIdSession $session
     * @return RedirectResponse
     * @throws \Exception
     */
    public function resolve(ResolveDigIdRequest $request, DigIdSession $session): RedirectResponse
    {
        $session->resolveResponse($request);

        // check if digid request worked
        if (!$session->isAuthorized()) {
            return $session->makeRedirectErrorResponse($session->getErrorKey());
        }

        // Authentication
        if ($session->session_request == 'auth') {
            return $this->_resolveAuth($session, $request);
        }

        // Fund request
        if ($session->session_request == 'fund_request') {
            return $this->_resolveFundRequest($session);
        }

        // Default unknown request type error
        return $session->makeRedirectErrorResponse('unknown_session_type');
    }

    /**
     * @param DigIdSession $session
     * @param ResolveDigIdRequest $request
     * @return RedirectResponse
     */
    private function _resolveAuth(DigIdSession $session, ResolveDigIdRequest $request): RedirectResponse
    {
        $identity = $session->digidBsnIdentity();

        if (!$identity) {
            if (!$session->implementation->digid_sign_up_allowed) {
                return $session->makeRedirectErrorResponse('uid_not_found');
            }

            $identity = Identity::make();
        }

        $proxy = Identity::makeAuthorizationShortTokenProxy();
        $identity->activateAuthorizationShortTokenProxy($proxy->exchange_token, $request->ip());

        $session->setIdentity($identity);
        $assignResult = $this->handleBsnAssign($session);

        // Redirect with an error
        if ($assignResult instanceof RedirectResponse) {
            return $assignResult;
        }

        return $session->makeRedirectResponse([
            'token' => $proxy->exchange_token,
        ], sprintf('%s/auth-link', rtrim($session->session_final_url, '/')));
    }

    /**
     * @param DigIdSession $session
     * @return RedirectResponse
     */
    private function _resolveFundRequest(DigIdSession $session): RedirectResponse
    {
        $assignResult = $this->handleBsnAssign($session);

        // Redirect with an error
        if ($assignResult instanceof RedirectResponse) {
            return $assignResult;
        }

        return $session->makeRedirectResponse(array_merge([
            'digid_success' => $assignResult ? 'signed_up' : 'signed_in',
        ]));
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
            return $session->makeRedirectErrorResponse('uid_dont_match');
        }

        // The digid bsn is already in the system but belongs to someone else
        if ($digidBsnIdentity && $digidBsnIdentity->address !== $sessionIdentity->address) {
            return $session->makeRedirectErrorResponse('uid_used');
        }

        // The session organization have bsn_enabled and
        if ($session->sessionOrganization()->bsn_enabled) {
            return (bool) $session->identity->setBsnRecord($session->digid_uid);
        }

        return false;
    }
}
