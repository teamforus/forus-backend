<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Identity\IdentityAuthorizeCodeRequest;
use App\Http\Requests\Api\Identity\IdentityAuthorizeTokenRequest;
use App\Http\Requests\Api\IdentityAuthorizationEmailTokenRequest;
use App\Http\Requests\Api\IdentityStoreRequest;
use App\Http\Requests\Api\IdentityUpdatePinCodeRequest;
use App\Http\Controllers\Controller;
use App\Models\Implementation;
use Illuminate\Http\Request;

class IdentityController extends Controller
{
    protected $identityRepo;
    protected $mailService;
    protected $recordRepo;

    public function __construct() {
        $this->mailService = resolve('forus.services.notification');
        $this->identityRepo = resolve('forus.services.identity');
        $this->recordRepo = resolve('forus.services.record');
    }

    public function getPublic()
    {
        return [
            'address' => auth()->id()
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
        $this->middleware('throttle', [10, 1 * 60]);

        $identityAddress = $this->identityRepo->makeByEmail(
            $request->input('records.primary_email'),
            $request->input('records')
        );

        $identityProxy = $this->identityRepo->makeIdentityPoxy($identityAddress);
        $clientType = $request->headers->get('Client-Type', 'general');
        $implementationKey = Implementation::activeKey();

        if (collect(['webshop', 'app-me_app'])->search($clientType) !== false) {
            $confirmationLink = url(
                '/api/v1/identity/proxy/confirmation/redirect/' .
                collect([
                    $identityProxy['exchange_token'],
                    $clientType,
                    $implementationKey
                ])->implode('/')
            );

            $this->mailService->sendEmailConfirmationLink(
                $request->input('records.primary_email'),
                $confirmationLink,
                $identityAddress
            );
        } else {
            $this->identityRepo->exchangeEmailConfirmationToken(
                $identityProxy['exchange_token']
            );
        }

        return collect($identityProxy)->only('access_token');
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
     *
     * @return array
     * @throws \Exception
     */
    public function proxyAuthorizationCode() {
        // TODO: Remove legacy transformation when android/ios is ready
        $proxy = collect(
            $this->identityRepo->makeAuthorizationCodeProxy()
        )->only([
            'access_token', 'exchange_token'
        ]);

        $proxy['auth_code'] = intval($proxy['exchange_token']);

        return $proxy->toArray();
    }

    /**
     * Make new token authorization proxy identity
     *
     * @return array
     * @throws \Exception
     */
    public function proxyAuthorizationToken() {
        // TODO: Remove legacy transformation when android/ios is ready
        $proxy = collect(
            $this->identityRepo->makeAuthorizationTokenProxy()
        )->only([
            'access_token', 'exchange_token'
        ]);

        $proxy['auth_token'] = $proxy['exchange_token'];

        return $proxy->toArray();
    }

    /**
     * Make new email authorization proxy identity
     *
     * @param IdentityAuthorizationEmailTokenRequest $request
     * @return array
     * @throws \Exception
     */
    public function proxyAuthorizationEmailToken(
        IdentityAuthorizationEmailTokenRequest $request
    ) {
        $this->middleware('throttle', [10, 1 * 60]);

        $email = $request->input('primary_email');
        $source = $request->input('source');

        $identityId = $this->recordRepo->identityAddressByEmail($email);
        $proxy = $this->identityRepo->makeAuthorizationEmailProxy($identityId);

        $link = url(sprintf(
            '/api/v1/identity/proxy/redirect/email/%s/%s',
            $source, $proxy['exchange_token']
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
            $this->mailService->loginViaEmail(
                $email,
                $identityId,
                $link,
                $platform
            );
        }

        return [
            'success' => !empty($proxy)
        ];
    }

    /**
     * Create and activate a short living token for current user
     *
     * @return array
     * @throws \Exception
     */
    public function proxyAuthorizationShortToken() {
        $proxy = $this->identityRepo->makeAuthorizationShortTokenProxy();

        $this->identityRepo->activateAuthorizationShortTokenProxy(
            auth()->id(), $proxy['exchange_token']
        );

        return array_only($proxy,[
            'exchange_token'
        ]);
    }

    /**
     *
     * @param string $shortToken
     * @return array
     */
    public function proxyExchangeAuthorizationShortToken(
        string $shortToken
    ) {
        return [
            'access_token' => $this->identityRepo
                ->exchangeAuthorizationShortTokenProxy($shortToken)
        ];
    }

    /**
     * Authorize code
     * @param IdentityAuthorizeCodeRequest $request
     * @return array|
     */
    public function proxyAuthorizeCode(IdentityAuthorizeCodeRequest $request) {
        $success = $this->identityRepo->activateAuthorizationCodeProxy(
            auth()->id(), $request->post('auth_code', '')
        );

        return compact('success');
    }

    /**
     * Authorize token
     * @param IdentityAuthorizeTokenRequest $request
     * @return array|
     */
    public function proxyAuthorizeToken(IdentityAuthorizeTokenRequest $request) {
        $success = $this->identityRepo->activateAuthorizationTokenProxy(
            auth()->id(), $request->post('auth_token', '')
        );

        return compact('success');
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
            $sourceUrl = Implementation::query()->where([
                'key' => $implementation
            ])->first()['url_' . $frontend];
        }

        $redirectUrl = $sourceUrl . "identity-restore?token=" . $emailToken;

        if ($source == 'app-me_app') {
            return view()->make('pages.auth.deep_link', compact('redirectUrl'));
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

        return [
            'access_token' => $this->identityRepo->activateAuthorizationEmailProxy($emailToken)
        ];
    }

    /**
     * @param IdentityAuthorizationEmailRedirectRequest $request
     * @param string $exchangeToken
     * @param string $clientType
     * @param string $implementationKey
     * @return \Illuminate\Contracts\View\View|void
     */
    public function emailConfirmationRedirect(
        IdentityAuthorizationEmailRedirectRequest $request,
        string $exchangeToken,
        string $clientType = 'webshop',
        string $implementationKey = 'general'
    ) {
        $token = $exchangeToken;
        $target = $request->input('target', '');
        if (!Implementation::isValidKey($implementationKey)) {
            abort(404, "Invalid implementation key.");
        }
        if (in_array($clientType, [
            'webshop', 'sponsor', 'provider', 'validator'
        ])) {
            $webShopUrl = Implementation::byKey($implementationKey);
            $webShopUrl = $webShopUrl['url_' . $clientType];
            return redirect($redirectUrl = sprintf(
                $webShopUrl . "confirmation/email/%s?%s",
                $exchangeToken,
                http_build_query(compact('target'))
            ));
        } elseif ($clientType == 'app-me_app') {
            $sourceUrl = config('forus.front_ends.app-me_app');
            $redirectUrl = sprintf(
                $sourceUrl . "identity-confirmation?%s",
                http_build_query(compact('token'))
            );
            return view()->make('pages.auth.deep_link', compact('redirectUrl'));
        }
        return abort(404);
    }
    
    /**
     * @param $exchangeToken
     * @return array
     */
    public function emailConfirmationExchange(
        $exchangeToken
    ) {
        return [
            'access_token' => $this->identityRepo->exchangeEmailConfirmationToken($exchangeToken)
        ];
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

        $proxyIdentityId = $this->identityRepo->proxyIdByAccessToken($accessToken);
        $identityAddress = $this->identityRepo->identityAddressByProxyId($proxyIdentityId);
        $proxyIdentityState = $this->identityRepo->proxyStateById($proxyIdentityId);

        switch ($proxyIdentityState) {
            case 'pending': return response()->json([
                "message" => 'pending'
            ]); break;
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
