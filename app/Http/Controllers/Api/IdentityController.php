<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Identity\IdentityAuthorizeCodeRequest;
use App\Http\Requests\Api\Identity\IdentityAuthorizeTokenRequest;
use App\Http\Requests\Api\Identity\IdentityDestroyRequest;
use App\Http\Requests\Api\IdentityAuthorizationEmailRedirectRequest;
use App\Http\Requests\Api\IdentityAuthorizationEmailTokenRequest;
use App\Http\Requests\Api\IdentityStoreRequest;
use App\Http\Requests\Api\IdentityStoreValidateEmailRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\IdentityResource;
use App\Mail\Forus\IdentityDestroyRequestMail;
use App\Models\Implementation;
use App\Models\Identity;
use App\Models\IdentityProxy;
use App\Traits\ThrottleWithMeta;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;

class IdentityController extends Controller
{
    use ThrottleWithMeta;

    /**
     * Get identity details
     *
     * @param BaseFormRequest $request
     * @return IdentityResource|array
     * @noinspection PhpUnused
     */
    public function getPublic(BaseFormRequest $request): IdentityResource|array
    {
        return IdentityResource::create($request->identity());
    }

    /**
     * Create new identity (registration)
     *
     * @param IdentityStoreRequest $request
     * @return IdentityResource
     * @noinspection PhpUnused
     */
    public function store(IdentityStoreRequest $request): IdentityResource
    {
        // make identity and exchange_token
        $identity = Identity::make($request->input('email'));
        $exchangeToken = $identity->makeIdentityPoxy()->exchange_token;
        $isMobile = in_array($request->client_type(), config('forus.clients.mobile'), true);

        $queryParams = sprintf("?%s", http_build_query(array_merge($request->only('target'), [
            'client_type' => $request->client_type(),
            'implementation_key' => $request->implementation_key(),
            'is_mobile' => $isMobile ? 1 : 0,
        ])));

        // send confirmation email
        $request->notification_repo()->sendEmailConfirmationLink(
            $identity->email,
            $request->client_type(),
            Implementation::emailFrom(),
            url("/api/v1/identity/proxy/confirmation/redirect/$exchangeToken$queryParams")
        );

        return IdentityResource::create($identity);
    }

    /**
     * Validate email for registration, format and if it's already in the system
     *
     * @param IdentityStoreValidateEmailRequest $request
     * @return JsonResponse
     * @noinspection PhpUnused
     */
    public function storeValidateEmail(IdentityStoreValidateEmailRequest $request): JsonResponse
    {
        $email = (string) $request->input('email', '');
        $used = !Identity::isEmailAvailable($email);

        return new JsonResponse([
            'email' => [
                'used' => $used,
                'unique' => !$used,
                'valid' => Validator::make(compact('email'), [
                    'email' => [
                        'required',
                        ...$request->emailRules(),
                    ],
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
     * @return View|RedirectResponse|Redirector
     * @throws \Exception
     * @noinspection PhpUnused
     */
    public function emailConfirmationRedirect(
        IdentityAuthorizationEmailRedirectRequest $request,
        string $exchangeToken
    ): View|Redirector|RedirectResponse {
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
                'type' => 'email_confirmation',
            ], compact('redirectUrl', 'exchangeToken')));
        }

        abort(404);
    }

    /**
     * Exchange email confirmation token for access_token
     *
     * @param BaseFormRequest $request
     * @param string $exchangeToken
     * @return JsonResponse
     * @noinspection PhpUnused
     */
    public function emailConfirmationExchange(
        BaseFormRequest $request,
        string $exchangeToken
    ): JsonResponse {
        return new JsonResponse([
            'access_token' => Identity::exchangeEmailConfirmationToken($exchangeToken, $request->ip())
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
        $email = $request->input('email');
        $source = sprintf('%s_%s', $request->implementation_key(), $request->client_type());
        $isMobile = in_array($request->client_type(), config('forus.clients.mobile'), true);
        $proxy = Identity::findByEmail($email)->makeAuthorizationEmailProxy();

        $redirect_link = url(sprintf(
            '/api/v1/identity/proxy/email/redirect/%s?%s',
            $proxy->exchange_token,
            http_build_query(array_merge([
                'target' => $request->input('target', ''),
                'is_mobile' => $isMobile ? 1 : 0
            ], $isMobile ? [] : [
                'client_type' => $request->client_type(),
                'implementation_key' => $request->implementation_key(),
            ]))
        ));

        $request->notification_repo()->loginViaEmail(
            $email,
            Implementation::emailFrom(),
            $redirect_link,
            $source
        );

        return new JsonResponse(null, 201);
    }

    /**
     * Redirect from email sign in link to one of the front-ends or
     * show a button with deep link to mobile app
     *
     * @param IdentityAuthorizationEmailRedirectRequest $request
     * @param string $emailToken
     * @return View|RedirectResponse|\Illuminate\Routing\Redirector
     * @noinspection PhpUnused
     */
    public function emailTokenRedirect(
        IdentityAuthorizationEmailRedirectRequest $request,
        string $emailToken
    ): View|Redirector|RedirectResponse {
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
     * @return JsonResponse
     * @noinspection PhpUnused
     */
    public function emailTokenExchange(BaseFormRequest $request, string $emailToken): JsonResponse
    {
        return new JsonResponse([
            'access_token' => Identity::activateAuthorizationEmailProxy($emailToken, $request->ip())
        ]);
    }

    /**
     * @param BaseFormRequest $request
     * @return JsonResponse
     */
    public function store2FASharedToken(BaseFormRequest $request): JsonResponse
    {
        $proxy = $request->identity()->makeAuthorizationEmailProxy();
        $proxy->inherit2FAStateFrom($request->identityProxy());

        $uri = config('forus.front_ends.app-me_app') . "identity-restore?%s";
        $request->identityProxy()->deactivateByLogout();

        return new JsonResponse([
            'redirect_url' => sprintf($uri, http_build_query([
                'token' => $proxy->exchange_token,
            ])),
        ]);
    }

    /**
     * Make new pin code authorization request
     *
     * @return \Illuminate\Http\JsonResponse
     * @noinspection PhpUnused
     */
    public function proxyAuthorizationCode(): JsonResponse
    {
        $proxy = Identity::makeAuthorizationCodeProxy();

        return new JsonResponse([
            'access_token' => $proxy->access_token,
            'auth_code' => (int) $proxy->exchange_token,
        ], 201);
    }

    /**
     * Authorize pin code authorization request
     *
     * @param IdentityAuthorizeCodeRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @noinspection PhpUnused
     */
    public function proxyAuthorizeCode(IdentityAuthorizeCodeRequest $request): JsonResponse
    {
        return new JsonResponse([
            'success' => $request->identity()->activateAuthorizationCodeProxy(
                $request->post('auth_code') ?: '',
                $request->ip(),
                $request->identityProxy()->is2FAConfirmed() ? $request->identityProxy() : null,
            ),
        ]);
    }

    /**
     * Make new auth token (qr-code) authorization request
     *
     * @return \Illuminate\Http\JsonResponse
     * @noinspection PhpUnused
     */
    public function proxyAuthorizationToken(): JsonResponse
    {
        $proxy = Identity::makeAuthorizationTokenProxy();

        return new JsonResponse([
            'access_token' => $proxy->access_token,
            'auth_token' => $proxy->exchange_token,
        ], 201);
    }

    /**
     * Authorize auth code (qr-code) authorization request
     *
     * @param IdentityAuthorizeTokenRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @noinspection PhpUnused
     */
    public function proxyAuthorizeToken(IdentityAuthorizeTokenRequest $request): JsonResponse
    {
        $authCode = $request->post('auth_token') ?: '';

        return new JsonResponse([
            'success' => $request->identity()->activateAuthorizationTokenProxy(
                $authCode,
                $request->ip(),
                $request->identityProxy()->is2FAConfirmed() ? $request->identityProxy() : null,
            ),
        ]);
    }

    /**
     * Create and activate a short living token for current user
     *
     * @param BaseFormRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     * @noinspection PhpUnused
     */
    public function proxyAuthorizationShortToken(BaseFormRequest $request): JsonResponse
    {
        $request->identity() or abort(403);

        $proxy = Identity::makeAuthorizationShortTokenProxy();
        $request->identity()->activateAuthorizationShortTokenProxy($proxy->exchange_token, $request->ip());

        return new JsonResponse([
            'exchange_token' => $proxy->exchange_token,
        ], 201);
    }

    /**
     * Exchange `short_token` for `access_token`
     *
     * @param string $shortToken
     * @return \Illuminate\Http\JsonResponse
     * @noinspection PhpUnused
     */
    public function proxyExchangeAuthorizationShortToken(string $shortToken): JsonResponse
    {
        $proxy = Identity::exchangeAuthorizationShortTokenProxy($shortToken);

        return new JsonResponse([
            'access_token' => $proxy->access_token
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
        $identityProxy = IdentityProxy::findByAccessToken($accessToken);

        if ($identityProxy?->isPending()) {
            return new JsonResponse(['message' => 'pending']);
        }

        if ($identityProxy?->isActive() && $identityProxy?->identity) {
            return new JsonResponse(['message' => 'active']);
        }

        return new JsonResponse(['message' => 'invalid']);
    }

    /**
     * Destroy access token
     *
     * @param BaseFormRequest $request
     * @return JsonResponse
     * @noinspection PhpUnused
     */
    public function proxyDestroy(BaseFormRequest $request): JsonResponse
    {
        return new JsonResponse([
            'success' => $request->identityProxy()->deactivateByLogout(),
        ]);
    }

    /**
     * @param IdentityDestroyRequest $request
     * @return JsonResponse
     * @throws \App\Exceptions\AuthorizationJsonException
     */
    public function destroy(IdentityDestroyRequest $request): JsonResponse
    {
        $this->maxAttempts = Config::get('forus.throttles.identity_destroy.attempts', 10);
        $this->decayMinutes = Config::get('forus.throttles.identity_destroy.decay', 10);
        $this->throttleWithKey('to_many_attempts', $request, 'delete_identity');

        if ($email = Config::get('forus.notification_mails.identity_destroy', false)) {
            $request->notification_repo()->sendSystemMail(
                $email, new IdentityDestroyRequestMail([
                    'email' => $request->identity()?->email ?: 'Identity has no email!',
                    'address' => $request->identity()?->address,
                    'comment' => $request->get('comment')
                ])
            );
        }

        return new JsonResponse();
    }
}
