<?php

namespace App\Http\Controllers;

use App\Http\Requests\DigID\ResolveDigIdRequest;
use App\Http\Requests\DigID\StartDigIdRequest;
use App\Models\Fund;
use App\Models\Prevalidation;
use App\Models\Voucher;
use App\Services\BackofficeApiService\Responses\EligibilityResponse;
use App\Services\BackofficeApiService\Responses\PartnerBsnResponse;
use App\Services\BackofficeApiService\Responses\ResidencyResponse;
use App\Services\DigIdService\Models\DigIdSession;
use App\Models\Identity;
use Illuminate\Http\RedirectResponse;

/**
 * Class DigIdController
 * @package App\Http\Controllers
 */
class DigIdController extends Controller
{
    /**
     * @param StartDigIdRequest $request
     * @return array
     */
    public function start(StartDigIdRequest $request): array
    {
        $digidSession = DigIdSession::createSession($request)->startAuthSession();

        if ($digidSession->state !== DigIdSession::STATE_PENDING_AUTH) {
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
        // check if session secret is match records
        if ($request->get('session_secret') !== $session->session_secret) {
            return redirect(url_extend_get_params($session->session_final_url, [
                'digid_error' => "unknown_error",
            ]));
        }

        // request BSN from digid and store in session
        $session->requestBsn(
            $request->get('rid', ''),
            $request->get('a-select-server', ''),
            $request->get('aselect_credentials', '')
        );

        // check if digid request worked
        if (!$session->isAuthorized()) {
            return $session->makeRedirectErrorResponse($session->getErrorKey());
        }

        // Authentication
        if ($session->session_request == 'auth') {
            return $this->_resolveAuth($session);
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
     * @return RedirectResponse
     * @throws \Exception
     */
    private function _resolveAuth(DigIdSession $session): RedirectResponse
    {
        $identity = $session->digidBsnIdentity();

        if (!$identity) {
            if (!$session->implementation->digid_sign_up_allowed) {
                return $session->makeRedirectErrorResponse('uid_not_found');
            }

            $identity = Identity::make();
            $identity->setBsnRecord($session->digid_uid);
        }

        $proxy = Identity::makeAuthorizationShortTokenProxy();
        $identity->activateAuthorizationShortTokenProxy($proxy->exchange_token);

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
        $fund = Fund::find($session->meta['fund_id']);
        $params = [];

        $hasBackoffice = $fund && $fund->fund_config && $fund->organization->backoffice_available;
        $assignResult = $this->handleBsnAssign($session);

        // Redirect with an error
        if ($assignResult instanceof RedirectResponse) {
            return $assignResult;
        }

        Prevalidation::assignAvailableToIdentityByBsn($session->identity);
        Voucher::assignAvailableToIdentityByBsn($session->identity);

        if ($fund->organization->bsn_enabled && $hasBackoffice) {
            $response = $fund->checkBackofficeIfAvailable($session->identity);
            $redirect = $this->handleBackofficeResponse($fund, $response);

            if (is_string($redirect)) {
                return redirect($redirect);
            }

            $params = $redirect;
        }

        return $session->makeRedirectResponse(array_merge([
            'digid_success' => $assignResult ? 'signed_up' : 'signed_in',
        ], $params));
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

        // The bsn is currently not assigned to anyone and the session account has no bsn assigned
        $shouldAssign = !$sessionIdentityBsn && !$digidBsnIdentity;

        // Identity already has a bsn attached, and it's different
        if ($sessionIdentityBsn && $sessionIdentityBsn !== $digidBsn) {
            return $session->makeRedirectErrorResponse('uid_dont_match');
        }

        // The digid bsn is already in the system but belongs to someone else
        if ($digidBsnIdentity && $digidBsnIdentity->address !== $sessionIdentity->address) {
            return $session->makeRedirectErrorResponse('uid_used');
        }

        // The session organization have bsn_enabled and
        if ($session->sessionOrganization()->bsn_enabled && $shouldAssign) {
            return (bool) $session->identity->setBsnRecord($session->digid_uid);
        }

        return false;
    }

    /**
     * @param Fund $fund
     * @param ResidencyResponse|PartnerBsnResponse|EligibilityResponse|null $response
     * @return string|array
     */
    protected function handleBackofficeResponse(Fund $fund, mixed $response): array|string
    {
        // backoffice not available
        if ($response === null) {
            return [];
        }

        // backoffice not responding
        if (!$response->getLog()->success()) {
            return $this->backofficeError('no_response', $fund->fund_config->backoffice_fallback);
        }

        // not resident
        if ($response instanceof ResidencyResponse && !$response->isResident()) {
            return $this->backofficeError('not_resident');
        }

        // is partner bsn
        if ($response instanceof PartnerBsnResponse) {
            return $this->backofficeError('taken_by_partner');
        }

        // not eligible
        if ($response instanceof EligibilityResponse && !$response->isEligible()) {
            if ($fund->fund_config->shouldRedirectOnIneligibility()) {
                // should redirect
                return $fund->fund_config->backoffice_ineligible_redirect_url;
            }

            // should show error
            return $this->backofficeError('not_eligible', true);
        }

        return [];
    }

    /**
     * @param string $error_key
     * @param bool $fallback
     * @return array
     */
    protected function backofficeError(string $error_key, bool $fallback = false): array
    {
        return [
            'backoffice_error' => 1,
            'backoffice_error_key' => $error_key,
            'backoffice_fallback' => $fallback ? 1 : 0,
        ];
    }
}
