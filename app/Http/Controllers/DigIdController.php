<?php

namespace App\Http\Controllers;

use App\Http\Requests\DigID\ResolveDigIdRequest;
use App\Http\Requests\DigID\StartDigIdRequest;
use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Prevalidation;
use App\Services\DigIdService\Models\DigIdSession;
use App\Services\Forus\Record\Repositories\Interfaces\IRecordRepo;
use Illuminate\Http\Request;

class DigIdController extends Controller
{

    protected $recordRepo;

    /**
     * DigIdController constructor.
     * @param IRecordRepo $recordRepo
     */
    public function __construct(IRecordRepo $recordRepo)
    {
        $this->recordRepo = $recordRepo;
    }

    /**
     * @param StartDigIdRequest $request
     * @return array|void
     */
    public function start(StartDigIdRequest $request)
    {
        $digidSession = DigIdSession::createSession(
            auth_address(),
            Implementation::activeModel(),
            self::makeFinalRedirectUrl($request)
        );

        $digidSession->startAuthSession(url(sprintf(
            '/api/v1/platform/digid/%s/resolve',
            $digidSession->session_uid
        )));

        if ($digidSession->state == DigIdSession::STATE_PENDING_AUTH) {
            return $digidSession->only('session_uid');
        }

        return abort(503, 'Unable to handle the request at the moment.');
    }

    /**
     * @param DigIdSession $session
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function redirect(DigIdSession $session) {
        return redirect($session->digid_auth_redirect_url);
    }

    /**
     * @param ResolveDigIdRequest $request
     * @param DigIdSession $session
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|void
     */
    public function resolve(
        ResolveDigIdRequest $request,
        DigIdSession $session
    ) {
        if ($request->get('session_secret') !== $session->session_secret) {
            return redirect(url_extend_get_params($session->session_final_url, [
                'digid_error' => "unknown_error",
            ]));
        }

        $session->requestBsn(
            $request->get('rid', ''),
            $request->get('a-select-server', ''),
            $request->get('aselect_credentials', '')
        );

        if ($session->state !== DigIdSession::STATE_AUTHORIZED) {
            return redirect(url_extend_get_params($session->session_final_url, [
                'digid_error' => $session->digid_error_code ?
                    "error_" . $session->digid_error_code : "error"
            ]));
        }

        $bsn = $session->digid_uid;
        $identity = $session->identity_address;
        $identity_bsn = $this->recordRepo->bsnByAddress($identity);
        $bsn_identity = $this->recordRepo->identityAddressByBsn($bsn);

        if ($identity_bsn && $identity_bsn !== $bsn_identity) {
            return redirect(url_extend_get_params($session->session_final_url, [
                'digid_error' => "uid_dont_match",
            ]));
        } elseif ($bsn_identity && $bsn_identity !== $identity) {
            return redirect(url_extend_get_params($session->session_final_url, [
                'digid_error' => "uid_used",
            ]));
        } else if (!$identity_bsn && !$bsn_identity) {
            $this->recordRepo->recordCreate(
                $session->identity_address, 'bsn', $session->digid_uid);
            Prevalidation::assignAvailableToIdentityByBsn($session->identity_address);

            return redirect(url_extend_get_params($session->session_final_url, [
                'digid_success' => 'signed_up'
            ]));
        }

        return redirect(url_extend_get_params($session->session_final_url, [
            'digid_success' => 'signed_in'
        ]));
    }

    /**
     * @param Request $request
     * @return bool|mixed|string
     */
    private static function makeFinalRedirectUrl(Request $request) {
        if ($request->input('redirect_type') == 'fund_request') {
            $fund = Fund::find($request->input('fund_id'));
            return $fund->urlWebshop(sprintf('/fund/%s/request', $fund->id));
        } else if ($request->input('redirect_type') == 'auth_webshop') {
            Implementation::activeModel()->urlWebshop();
        } else if ($request->input('redirect_type') == 'auth_sponsor') {
            Implementation::activeModel()->urlSponsorDashboard();
        } else if ($request->input('redirect_type') == 'auth_provider') {
            Implementation::activeModel()->urlSponsorDashboard();
        } else if ($request->input('redirect_type') == 'auth_validator') {
            Implementation::activeModel()->urlSponsorDashboard();
        }

        return false;
    }
}
