<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Identity\IdentityAuthorizeCodeRequest;
use App\Http\Requests\Api\Identity\IdentityAuthorizeTokenRequest;
use App\Http\Requests\Api\IdentityAuthorizationEmailTokenRequest;
use App\Http\Requests\Api\IdentityStoreRequest;
use App\Http\Requests\Api\IdentityUpdatePinCodeRequest;
use App\Http\Controllers\Controller;
use App\Models\Implementation;
use App\Services\Forus\MailNotification\MailService;
use Illuminate\Http\Request;

class IdentityController extends Controller
{
    protected $identityRepo;
    protected $recordRepo;

    /** @var MailService $mailService */
    protected $mailService;

    public function __construct() {
        $this->identityRepo = app()->make('forus.services.identity');
        $this->recordRepo = app()->make('forus.services.record');

        $this->mailService = app()->make('forus.services.mail_notification');
    }

    public function getPublic()
    {
        return [
            'address' => auth()->user()->getAuthIdentifier()
        ];
    }

    /**
     * Create new identity
     *
     * @param IdentityStoreRequest $request
     * @return array
     * @throws \Exception
     */
    public function store(
        IdentityStoreRequest $request
    ) {
        $identityAddress = $this->identityRepo->make(
            $request->input('pin_code'),
            $request->input('records')
        );

        $identityProxyId = $this->identityRepo->makeIdentityPoxy(
            $identityAddress
        );

        $this->recordRepo->categoryCreate($identityAddress, "Relaties");

        $this->mailService->addConnection(
            $identityAddress,
            $this->mailService::TYPE_EMAIL,
            $request->input('records.primary_email')
        );

        return [
            'access_token' => $this->identityRepo->getProxyAccessToken(
                $identityProxyId
            )
        ];
    }

    /**
     * @param IdentityUpdatePinCodeRequest $request
     * @return array
     * @throws \Exception
     */
    public function updatePinCode(IdentityUpdatePinCodeRequest $request)
    {
        $success = $this->identityRepo->updatePinCode(
            $request->get('proxyIdentity'),
            $request->input('pin_code'),
            $request->input('old_pin_code')
        );

        return compact('success');
    }

    /**
     * @param Request $request
     * @param string $pinCode
     * @return array
     * @throws \Exception
     */
    public function checkPinCode(Request $request, string $pinCode)
    {
        $success = $this->identityRepo->cmpPinCode(
            $request->get('proxyIdentity'),
            $pinCode
        );

        return compact('success');
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function proxyDestroy(Request $request) {
        $proxyDestroy = $request->get('proxyIdentity');

        $this->identityRepo->destroyProxyIdentity($proxyDestroy);

        return response()->json([], 200);
    }

    /**
     * Make new code authorization proxy identity
     * @return array
     */
    public function proxyAuthorizationCode() {
        return $this->identityRepo->makeAuthorizationCodeProxy();
    }

    /**
     * Make new token authorization proxy identity
     * @return array
     */
    public function proxyAuthorizationToken() {
        return $this->identityRepo->makeAuthorizationTokenProxy();
    }

    /**
     * Make new email authorization proxy identity
     * @param IdentityAuthorizationEmailTokenRequest $request
     * @return array
     */
    public function proxyAuthorizationEmailToken(
        IdentityAuthorizationEmailTokenRequest $request
    ) {
        $email = $request->input('primary_email');
        $source = $request->input('source');

        $identityId = $this->recordRepo->identityIdByEmail($email);
        $proxy = $this->identityRepo->makeAuthorizationEmailProxy($identityId);

        $link = url(sprintf(
            '/api/v1/identity/proxy/redirect/email/%s/%s',
            $source, $proxy['auth_email_token']
        ));

        $platform = '';

        if (strpos($source, '_webshop') !== false) {
            $platform = 'de webshop';
        } else if (strpos($source, '_sponsor') !== false) {
            $platform = 'het dashboard';
        } else if (strpos($source, '_provider') !== false) {
            $platform = 'het dashboard';
        } else if (strpos($source, '_validator') !== false) {
            $platform = 'het dashboard';
        } else if (strpos($source, 'app-me_app') !== false) {
            $platform = 'Me';
        }

        if (!empty($proxy)) {
            $this->mailService->loginViaEmail($identityId, $link, $platform);
        }

        return [
            'success' => !empty($proxy)
        ];
    }

    /**
     * Authorize code
     * @param IdentityAuthorizeCodeRequest $request
     * @return array|
     */
    public function proxyAuthorizeCode(IdentityAuthorizeCodeRequest $request) {
        $status = $this->identityRepo->activateAuthorizationCodeProxy(
            auth()->user()->getAuthIdentifier(),
            $request->post('auth_code', '')
        );

        if ($status === "not-found") {
            return abort(404, trans(
                'identity-proxy.code.' . $status
            ));
        } elseif ($status === "not-pending") {
            return abort(403, trans(
                'identity-proxy.code.' . $status
            ));
        } elseif ($status === "expired") {
            return abort(403, trans(
                'identity-proxy.code.' . $status
            ));
        } elseif ($status === true) {
            return [
                'success' => true
            ];
        }

        return [
            'success' => false
        ];
    }

    /**
     * Authorize token
     * @param IdentityAuthorizeTokenRequest $request
     * @return array|
     */
    public function proxyAuthorizeToken(IdentityAuthorizeTokenRequest $request) {
        $status = $this->identityRepo->activateAuthorizationTokenProxy(
            auth()->user()->getAuthIdentifier(),
            $request->post('auth_token', '')
        );

        if ($status === "not-found") {
            return abort(404, trans(
                'identity-proxy.code.' . $status
            ));
        } elseif ($status === "not-pending") {
            return abort(403, trans(
                'identity-proxy.code.' . $status
            ));
        } elseif ($status === "expired") {
            return abort(403, trans(
                'identity-proxy.code.' . $status
            ));
        } elseif ($status === true) {
            return [
                'success' => true
            ];
        }

        return [
            'success' => false
        ];
    }

    /**
     * Redirect email token
     * @param string $source
     * @param string $emailToken
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function proxyRedirectEmail(
        string $source,
        string $emailToken
    ) {
        if (Implementation::keysAvailable()->search($source) === false) {
            abort(404);
        }

        list($implementation, $frontend) = explode('_', $source);

        if ($source == 'app-me_app') {
            $sourceUrl = config('forus.front_ends.app-me_app');
        } else if ($implementation == 'general') {
            $sourceUrl = Implementation::general_urls()['url_' . $frontend];
        } else {
            $sourceUrl = Implementation::query()->where('key', $implementation)->first()['url_' . $frontend];
        }

        $redirectUrl = $sourceUrl . "identity-restore?token=" . $emailToken;

        if ($source == 'app-me_app') {
            return view()->make('auth.deep_link', compact('redirectUrl'));
        };

        return redirect($redirectUrl);
    }

    /**
     * Authorize email token
     * @param string $source
     * @param string $emailToken
     * @return array
     */
    public function proxyAuthorizeEmail(
        string $source,
        string $emailToken
    ) {
        if (Implementation::keysAvailable()->search($source) === false) {
            abort(404);
        }

        $access_token = $this->identityRepo->activateAuthorizationEmailProxy(
            $emailToken
        );

        return compact('access_token');
    }

    /**
     * Check access_token state
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkToken(
        Request $request
    ) {
        $accessToken = $request->input('access_token');

        $proxyIdentityId = $this->identityRepo->proxyIdByAccessToken(
            $accessToken
        );

        $proxyIdentityState = $this->identityRepo->proxyStateById(
            $proxyIdentityId
        );

        $identityAddress = $this->identityRepo->identityAddressByProxyId(
            $proxyIdentityId
        );

        if ($accessToken && $proxyIdentityState != 'active') {
            switch ($proxyIdentityState) {
                case 'pending': {
                    return response()->json([
                        "message" => 'pending'
                    ]);
                } break;
            }
        }

        if (!$accessToken || !$proxyIdentityId || !$identityAddress) {
            return response()->json([
                "message" => 'invalid'
            ]);
        }

        return response()->json([
            "message" => 'active'
        ]);
    }
}
