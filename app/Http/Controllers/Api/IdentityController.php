<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Identity\IdentityAuthorizeCodeRequest;
use App\Http\Requests\Api\Identity\IdentityAuthorizeTokenRequest;
use App\Http\Requests\Api\IdentityAuthorizationEmailRedirectRequest;
use App\Http\Requests\Api\IdentityAuthorizationEmailTokenRequest;
use App\Http\Requests\Api\IdentityStoreRequest;
use App\Http\Requests\Api\IdentityStoreValidateEmailRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\BaseFormRequest;
use App\Models\Implementation;
use App\Traits\ThrottleLoginAttempts;
use Illuminate\Http\JsonResponse;

/**
 * Class IdentityController
 * @package App\Http\Controllers\Api
 */
class IdentityController extends Controller
{
    /**
     * Get identity details
     *
     * @param BaseFormRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @noinspection PhpUnused
     */
    public function getPublic(BaseFormRequest $request): JsonResponse
    {
        $address = $request->auth_address();
        $email = $request->identity_repo()->getPrimaryEmail($address);
        $bsn = !empty($request->records_repo()->bsnByAddress($address));

        return response()->json(compact('address', 'email', 'bsn'));
    }

    /**
     * Create new identity (registration)
     *
     * @param IdentityStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     * @noinspection PhpUnused
     */
    public function store(
        IdentityStoreRequest $request
    ): JsonResponse {
        // client type, key and primary email
        $clientKey = implementation_key();
        $clientType = client_type();
        $primaryEmail = $request->input('email', $request->input('records.primary_email'));

        // build records list and remove bsn and primary_email
        $records = collect($request->input('records', []));
        $records = $records->filter(function($value, $key) {
            return !empty($value) && !in_array($key, ['bsn', 'primary_email']);
        })->toArray();

        // make identity and exchange_token
        $identityAddress = $request->identity_repo()->makeByEmail($primaryEmail, $records);
        $identityProxy = $request->identity_repo()->makeIdentityPoxy($identityAddress);
        $exchangeToken = $identityProxy['exchange_token'];
        $isMobile = in_array($clientType, config('forus.clients.mobile'), true);

        $queryParams = sprintf("?%s", http_build_query(array_merge(
            $request->only('target'), [
                'client_type' => $clientType,
                'implementation_key' => $clientKey,
                'is_mobile' => $isMobile ? 1 : 0,
            ]
        )));

        // build confirmation link
        $confirmationLink = url(sprintf(
            '/api/v1/identity/proxy/confirmation/redirect/%s%s',
            $exchangeToken,
            $queryParams
        ));

        // send confirmation email
        $request->notification_repo()->sendEmailConfirmationLink(
            $primaryEmail,
            $clientType,
            Implementation::emailFrom(),
            $confirmationLink
        );

        return response()->json(null, 201);
    }

    /**
     * Validate email for registration, format and if it's already in the system
     *
     * @param IdentityStoreValidateEmailRequest $request
     * @return JsonResponse
     * @noinspection PhpUnused
     */
    public function storeValidateEmail(
        IdentityStoreValidateEmailRequest $request
    ): JsonResponse {
        $email = (string) $request->input('email', '');
        $used = !$request->identity_repo()->isEmailAvailable($email);

        return response()->json([
            'email' => [
                'used' => $used,
                'unique' => !$used,
                'valid' => validate_data(compact('email'), [
                    'email' => 'required|email'
                ])->passes(),
            ]
        ]);
    }

    /**
     * Redirect from email confirmation link to one of the front-ends or
     * show a button with deep link to mobile app
     *
     * @param IdentityAuthorizationEmailRedirectRequest $request
     * @param string $exchangeToken
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|void
     * @throws \Exception
     * @noinspection PhpUnused
     */
    public function emailConfirmationRedirect(
        IdentityAuthorizationEmailRedirectRequest $request,
        string $exchangeToken
    ) {
        $token = $exchangeToken;
        $isMobile = $request->input('is_mobile', false);

        $target = $request->input('target', false);
        $clientType = $request->input('client_type', '');
        $implementationKey = $request->input('implementation_key');

        if ((!$isMobile || $clientType) &&
            !in_array($clientType, array_flatten(config('forus.clients')), true)) {
            abort(404, "Invalid client type.");
        }

        if ((!$isMobile || $clientType) &&
            !Implementation::isValidKey($implementationKey)) {
            abort(404, "Invalid implementation key.");
        }

        $isWebFrontend = in_array($clientType, array_merge(
            (array) config('forus.clients.webshop', []),
            (array) config('forus.clients.dashboards', [])
        ), true);

        if ($isWebFrontend) {
            $webShopUrl = Implementation::byKey($implementationKey);
            $webShopUrl = $webShopUrl['url_' . $clientType];

            return redirect(sprintf(
                $webShopUrl . "confirmation/email/%s?%s",
                $exchangeToken,
                http_build_query(compact('target'))
            ));
        }

        if ($isMobile) {
            $sourceUrl = config('forus.front_ends.app-me_app');
            $redirectUrl = sprintf(
                $sourceUrl . "identity-confirmation?%s",
                http_build_query(compact('token'))
            );

            return view()->make('pages.auth.deep_link', array_merge([
                'type' => 'email_confirmation'
            ], compact('redirectUrl', 'exchangeToken')));
        }

        abort(404);
    }

    /**
     * Exchange email confirmation token for access_token
     *
     * @param BaseFormRequest $request
     * @param string $exchangeToken
     * @return \Illuminate\Http\JsonResponse
     * @noinspection PhpUnused
     */
    public function emailConfirmationExchange(
        BaseFormRequest $request,
        string $exchangeToken
    ): JsonResponse {
        return response()->json([
            'access_token' => $request->identity_repo()->exchangeEmailConfirmationToken($exchangeToken)
        ]);
    }

    /**
     * Make new email authorization request
     *
     * @param IdentityAuthorizationEmailTokenRequest $request
     * @return JsonResponse
     * @noinspection PhpUnused
     */
    public function proxyAuthorizationEmailToken(
        IdentityAuthorizationEmailTokenRequest $request
    ): JsonResponse {
        // TODO: remove `primary_email` when iOS is ready
        $email = $request->input($request->has('email') ? 'email' : 'primary_email');
        $source = sprintf('%s_%s', implementation_key(), client_type());
        $isMobile = in_array(client_type(), config('forus.clients.mobile'), true);

        $identityId = $request->records_repo()->identityAddressByEmail($email);
        $proxy = $request->identity_repo()->makeAuthorizationEmailProxy($identityId);

        $redirect_link = url(sprintf(
            '/api/v1/identity/proxy/email/redirect/%s?%s',
            $proxy['exchange_token'],
            http_build_query(array_merge([
                'target' => $request->input('target', ''),
                'is_mobile' => $isMobile ? 1 : 0
            ], $isMobile ? [] : [
                'client_type' => client_type(),
                'implementation_key' => implementation_key(),
            ]))
        ));

        $request->notification_repo()->loginViaEmail(
            $email,
            Implementation::emailFrom(),
            $redirect_link,
            $source
        );

        return response()->json(null, 201);
    }

    /**
     * Redirect from email sign in link to one of the front-ends or
     * show a button with deep link to mobile app
     *
     * @param IdentityAuthorizationEmailRedirectRequest $request
     * @param string $emailToken
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @noinspection PhpUnused
     */
    public function emailTokenRedirect(
        IdentityAuthorizationEmailRedirectRequest $request,
        string $emailToken
    ) {
        $exchangeToken = $emailToken;
        $clientType = $request->input('client_type');
        $implementationKey = $request->input('implementation_key');
        $isMobile = $request->input('is_mobile', false);

        if ((!$isMobile || $clientType) &&
            !in_array($clientType, array_flatten(config('forus.clients')), true)) {
            abort(404, "Invalid client type.");
        }

        if ((!$isMobile || $clientType) &&
            !Implementation::isValidKey($implementationKey)) {
            abort(404, "Invalid implementation key.");
        }

        if ($isMobile) {
            $sourceUrl = config('forus.front_ends.app-me_app');
        } else {
            $sourceUrl = Implementation::byKey($implementationKey)->urlFrontend($clientType);
        }

        $redirectUrl = sprintf(
            $sourceUrl . "identity-restore?%s",
            http_build_query(array_filter([
                'token' => $emailToken,
                'target' => $request->input('target')
            ], static function($var) {
                return !empty($var);
            }))
        );

        if ($isMobile) {
            return view('pages.auth.deep_link', array_merge([
                'type' => 'email_sign_in'
            ], compact('redirectUrl', 'exchangeToken')));
        }

        return redirect($redirectUrl);
    }

    /**
     * Exchange email sign in token for access_token
     *
     * @param BaseFormRequest $request
     * @param string $emailToken
     * @return \Illuminate\Http\JsonResponse
     * @noinspection PhpUnused
     */
    public function emailTokenExchange(
        BaseFormRequest $request,
        string $emailToken
    ): JsonResponse {
        return response()->json([
            'access_token' => $request->identity_repo()->activateAuthorizationEmailProxy($emailToken)
        ]);
    }

    /**
     * Make new pin code authorization request
     *
     * @param BaseFormRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @noinspection PhpUnused
     */
    public function proxyAuthorizationCode(
        BaseFormRequest $request
    ): JsonResponse {
        $proxy = $request->identity_repo()->makeAuthorizationCodeProxy();

        return response()->json([
            'access_token' => $proxy['access_token'],
            'auth_code' => (int) $proxy['exchange_token'],
        ], 201);
    }

    /**
     * Authorize pin code authorization request
     *
     * @param IdentityAuthorizeCodeRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @noinspection PhpUnused
     */
    public function proxyAuthorizeCode(
        IdentityAuthorizeCodeRequest $request
    ): JsonResponse {
        $request->identity_repo()->activateAuthorizationCodeProxy(
            $request->auth_address(),
            $request->post('auth_code', '')
        );

        return response()->json(null);
    }

    /**
     * Make new auth token (qr-code) authorization request
     *
     * @param BaseFormRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @noinspection PhpUnused
     */
    public function proxyAuthorizationToken(
        BaseFormRequest $request
    ): JsonResponse {
        $proxy = $request->identity_repo()->makeAuthorizationTokenProxy();

        return response()->json([
            'access_token' => $proxy['access_token'],
            'auth_token' => $proxy['exchange_token'],
        ], 201);
    }

    /**
     * Authorize auth code (qr-code) authorization request
     *
     * @param IdentityAuthorizeTokenRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @noinspection PhpUnused
     */
    public function proxyAuthorizeToken(
        IdentityAuthorizeTokenRequest $request
    ): JsonResponse {
        $request->identity_repo()->activateAuthorizationTokenProxy(
            $request->auth_address(),
            $request->post('auth_token', '')
        );

        return response()->json(null);
    }

    /**
     * Create and activate a short living token for current user
     *
     * @param BaseFormRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     * @noinspection PhpUnused
     */
    public function proxyAuthorizationShortToken(
        BaseFormRequest $request
    ): JsonResponse {
        $proxy = $request->identity_repo()->makeAuthorizationShortTokenProxy();
        $exchange_token = $proxy['exchange_token'];

        $request->identity_repo()->activateAuthorizationShortTokenProxy(
            $request->auth_address(),
            $exchange_token
        );

        return response()->json(compact('exchange_token'), 201);
    }

    /**
     * Exchange `short_token` for `access_token`
     *
     * @param BaseFormRequest $request
     * @param string $shortToken
     * @return \Illuminate\Http\JsonResponse
     * @noinspection PhpUnused
     */
    public function proxyExchangeAuthorizationShortToken(
        BaseFormRequest $request,
        string $shortToken
    ): JsonResponse {
        $token = $request->identity_repo()->exchangeAuthorizationShortTokenProxy($shortToken);

        return response()->json([
            'access_token' => $token
        ]);
    }

    /**
     * Check access_token state
     *
     * @param BaseFormRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @noinspection PhpUnused
     */
    public function checkToken(BaseFormRequest $request): JsonResponse
    {
        $accessToken = $request->header('Access-Token');
        $proxyIdentityId = $request->identity_repo()->proxyIdByAccessToken($accessToken);
        $identityAddress = $request->identity_repo()->identityAddressByProxyId($proxyIdentityId);
        $proxyIdentityState = $request->identity_repo()->proxyStateById($proxyIdentityId);
        $message = 'active';

        if ($proxyIdentityState === 'pending') {
            $message = 'pending';
        } elseif (!$accessToken || !$proxyIdentityId || !$identityAddress) {
            $message = 'invalid';
        }

        return response()->json(compact('message'));
    }

    /**
     * Destroy an access token
     *
     * @param BaseFormRequest $request
     * @return JsonResponse
     * @noinspection PhpUnused
     */
    public function proxyDestroy(
        BaseFormRequest $request
    ): JsonResponse {
        $proxyDestroy = $request->get('proxyIdentity');

        $request->identity_repo()->destroyProxyIdentity($proxyDestroy);

        return response()->json(null);
    }
}
