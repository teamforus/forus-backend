<?php

namespace App\Http\Controllers;

use App\Http\Requests\DigID\ResolveDigIdRequest;
use App\Http\Requests\DigID\StartDigIdRequest;
use App\Models\Fund;
use App\Models\Prevalidation;
use App\Models\Voucher;
use App\Services\DigIdService\Models\DigIdSession;

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
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function redirect(DigIdSession $session)
    {
        return redirect($session->digid_auth_redirect_url);
    }

    /**
     * @param ResolveDigIdRequest $request
     * @param DigIdSession $session
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Exception
     */
    public function resolve(ResolveDigIdRequest $request, DigIdSession $session)
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

        // check if digid request went well and redirect to final url with
        // error core if not
        if (!$session->isAuthorized()) {
            return redirect(url_extend_get_params($session->session_final_url, [
                'digid_error' => $session->getErrorKey()
            ]));
        }

        switch ($session->session_request) {
            case 'auth': return $this->_resolveAuth($request, $session);
            case 'fund_request': return $this->_resolveFundRequest($request, $session);
        }

        return redirect(url_extend_get_params($session->session_final_url, [
            'digid_error' => 'unknown_session_type',
        ]));
    }

    /**
     * @param ResolveDigIdRequest $request
     * @param DigIdSession $session
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Exception
     */
    private function _resolveAuth(ResolveDigIdRequest $request, DigIdSession $session)
    {
        $identity = $request->records_repo()->identityAddressByBsn($session->digid_uid);

        if (empty($identity)) {
            return redirect(sprintf(
                 '%s/?digid_error=uid_not_found',
                rtrim($session->session_final_url, '/')
            ));
        }

        $identityRepo = $request->identity_repo();
        $proxy = $identityRepo->makeAuthorizationShortTokenProxy();
        $identityRepo->activateAuthorizationShortTokenProxy($identity, $proxy['exchange_token']);

        return redirect(sprintf(
            '%s/auth-link?token=%s',
            rtrim($session->session_final_url, '/'),
            $proxy['exchange_token']
        ));
    }

    /**
     * @param ResolveDigIdRequest $request
     * @param DigIdSession $session
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    private function _resolveFundRequest(ResolveDigIdRequest $request, DigIdSession $session)
    {
        $recordRepo = $request->records_repo();
        $bsn = $session->digid_uid;
        $fund = Fund::find($session->meta['fund_id']);
        $identity = $session->identity_address;
        $identity_bsn = $recordRepo->bsnByAddress($identity);
        $bsn_identity = $recordRepo->identityAddressByBsn($bsn);
        $params = [];

        if ($identity_bsn && $bsn !== $identity_bsn) {
            return redirect(url_extend_get_params($session->session_final_url, [
                'digid_error' => "uid_dont_match",
            ]));
        }

        if ($bsn_identity && $bsn_identity !== $identity) {
            return redirect(url_extend_get_params($session->session_final_url, [
                'digid_error' => "uid_used",
            ]));
        }

        $isFirstSignUp = !$identity_bsn && !$bsn_identity;
        $hasBackoffice = $fund && $fund->fund_config && $fund->organization->backoffice_available;

        if ($fund->organization->bsn_enabled && $isFirstSignUp) {
            $recordRepo->setBsnRecord($identity, $bsn);
        }

        Prevalidation::assignAvailableToIdentityByBsn($identity);
        Voucher::assignAvailableToIdentityByBsn($identity);

        if ($fund->organization->bsn_enabled && $hasBackoffice) {
            $backofficeResponse = $fund->checkBackofficeIfAvailable($identity);

            if (!$backofficeResponse->isEligible() && $backofficeResponse->getLog()->success() &&
                !empty($fund->fund_config->backoffice_not_eligible_redirect_url)) {
                return redirect($fund->fund_config->backoffice_not_eligible_redirect_url);
            }

            if ($backofficeResponse && !$backofficeResponse->getLog()->success()) {
                $params['backoffice_error'] = 1;
                $params['backoffice_fallback'] = $fund->fund_config->backoffice_fallback;
            }
        }

        return redirect(url_extend_get_params($session->session_final_url, array_merge([
            'digid_success' => $isFirstSignUp ? 'signed_up' : 'signed_in',
        ], $params)));
    }
}
