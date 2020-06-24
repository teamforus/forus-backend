<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Identity\IdentityAuthorizeCodeRequest;
use App\Http\Requests\Api\Identity\IdentityAuthorizeTokenRequest;
use App\Http\Requests\Api\IdentityAuthorizationEmailRedirectRequest;
use App\Http\Requests\Api\IdentityAuthorizationEmailTokenRequest;
use App\Http\Requests\Api\IdentityStoreRequest;
use App\Http\Requests\Api\IdentityStoreValidateEmailRequest;
use App\Http\Requests\Api\IdentityUpdatePinCodeRequest;
use App\Http\Controllers\Controller;
use App\Models\Implementation;
use App\Services\Forus\Identity\Repositories\Interfaces\IIdentityRepo;
use App\Services\Forus\Notification\NotificationService;
use App\Services\Forus\Record\Repositories\Interfaces\IRecordRepo;
use Illuminate\Http\Request;

/**
 * Class IdentityController
 * @property IIdentityRepo $identityRepo
 * @property IRecordRepo $recordRepo
 * @property NotificationService $mailService
 * @package App\Http\Controllers\Api
 */
class IdentityFallbackController extends Controller
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
        $address = auth()->id();
        $email = $this->identityRepo->getPrimaryEmail($address);
        $bsn = !empty($this->recordRepo->bsnByAddress($address));

        return response()->json(compact('address', 'email', 'bsn'));
    }

    /**
     * Create new identity
     *
     * @param IdentityStoreRequest $request
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public function store(
        IdentityStoreRequest $request
    ) {
        $this->middleware('throttle', [10, 1 * 60]);

        // client type, key and target primary email
        $clientType = client_type(config('forus.clients.default'));
        $clientKey = implementation_key('general');
        $primaryEmail = $request->input('email', $request->input(
            'records.primary_email'
        ));

        // build records list and remove bsn and primary_email
        $records = collect($request->input('records', []));
        $records = $records->filter(function($value, $key) {
            return !empty($value) && !in_array($key, ['bsn', 'primary_email']);
        })->toArray();

        // make identity and exchange_token
        $identityAddress = $this->identityRepo->makeByEmail($primaryEmail, $records);
        $identityProxy = $this->identityRepo->makeIdentityPoxy($identityAddress);
        $exchangeToken = $identityProxy['exchange_token'];

        // registration through webshop or mobile app
        $isWebshopOrMobile = in_array($clientType, array_merge(
            config('forus.clients.webshop'),
            config('forus.clients.mobile')
        ));

        // send confirmation email
        if ($isWebshopOrMobile || $request->input('confirm', false)) {
            $isMobile = in_array($clientType, config('forus.clients.mobile'));

            // build confirmation link
            $confirmationLink = url(sprintf(
                '/api/v1/identity/proxy/confirmation/redirect/%s/%s/%s%s',
                $exchangeToken,
                $clientType,
                $clientKey,
                $isMobile ? '' : ('?' . http_build_query($request->only('target')))
            ));

            $this->mailService->sendEmailConfirmationLink(
                $primaryEmail,
                Implementation::emailFrom(),
                $confirmationLink
            );
            
            // TODO: always require confirmation
            // otherwise (dashboards) skip confirmation part
        } else {
            $this->identityRepo->exchangeEmailConfirmationToken($exchangeToken);
        }

        return collect($identityProxy)->only('access_token');
    }

    /**
     * @param IdentityStoreValidateEmailRequest $request
     * @return array
     * @throws \Exception
     */
    public function storeValidateEmail(IdentityStoreValidateEmailRequest $request) {
        $this->middleware('throttle', [10, 1 * 60]);

        $email = (string) $request->input('email', '');
        $used = !identity_repo()->isEmailAvailable($email);

        return [
            'email' => [
                'used' => $used,
                'unique' => !$used,
                'valid' => validate_data(compact('email'), [
                    'email' => 'required|email'
                ])->passes(),
            ]
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

        $email = $request->input('email', $request->input('primary_email'));
        $source = $request->input('source');

        $identityId = $this->recordRepo->identityAddressByEmail($email);
        $proxy = $this->identityRepo->makeAuthorizationEmailProxy($identityId);

        $confirmation_link = url(sprintf(
            '/api/v1/identity/proxy/redirect/email/%s/%s?target=%s',
            $source,
            $proxy['exchange_token'],
            $request->input('target', '')
        ));

        if (!empty($proxy)) {
            $this->mailService->loginViaEmail(
                $email,
                Implementation::emailFrom(),
                $confirmation_link,
                $source
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
     *
     * @param IdentityAuthorizationEmailRedirectRequest $request
     * @param string $source
     * @param string $emailToken
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function proxyRedirectEmail(
        IdentityAuthorizationEmailRedirectRequest $request,
        string $source,
        string $emailToken
    ) {
        if (Implementation::keysAvailable()->search($source) === false) {
            abort(404);
        }

        [$implementation, $frontend] = explode('_', $source);

        $isMobile = in_array($source, config('forus.clients.mobile'), true);

        if ($isMobile) {
            $sourceUrl = config('forus.front_ends.app-me_app');
        } else if ($implementation === 'general') {
            $sourceUrl = Implementation::general_urls()['url_' . $frontend];
        } else {
            $sourceUrl = Implementation::query()->where([
                'key' => $implementation
            ])->first()['url_' . $frontend];
        }

        $redirectUrl = sprintf(
            $sourceUrl . "identity-restore?%s",
            http_build_query(array_filter([
                'token' => $emailToken,
                'target' => $request->input('target', null)
            ], static function($var) {
                return !empty($var);
            }))
        );

        if ($isMobile) {
            return view('pages.auth.deep_link', array_merge([
                'type' => 'email_sign_in',
                'exchangeToken' => $emailToken
            ], compact('redirectUrl')));
        }

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
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|void
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

        $isMobile = in_array($clientType, config('forus.clients.mobile'));
        $isWebshopOrDashboard = in_array($clientType, array_merge(
            config('forus.clients.webshop'),
            config('forus.clients.dashboards')
        ));

        if ($isWebshopOrDashboard) {
            $webShopUrl = Implementation::byKey($implementationKey);
            $webShopUrl = $webShopUrl['url_' . $clientType];

            return redirect($redirectUrl = sprintf(
                $webShopUrl . "confirmation/email/%s?%s",
                $exchangeToken,
                http_build_query(compact('target'))
            ));
        } elseif ($isMobile) {
            $sourceUrl = config('forus.front_ends.app-me_app');
            $redirectUrl = sprintf(
                $sourceUrl . "identity-confirmation?%s",
                http_build_query(compact('token'))
            );

            return view('pages.auth.deep_link', compact('redirectUrl'));
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
        $accessToken = $request->header('Access-Token', null);

        // TODO: deprecated, remove when mobile apps are ready
        if (!$accessToken) {
            $accessToken = $request->get('access_token', null);
        }

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
