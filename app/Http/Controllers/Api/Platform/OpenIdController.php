<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\OpenID\StartOpenIdRequest;
use App\Models\Fund;
use App\Models\Identity;
use App\Services\OpenIdService\Models\OpenIdSession;
use App\Services\OpenIdService\OpenIdException;
use App\Services\OpenIdService\OpenIdService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

class OpenIdController extends Controller
{
    private const string FALLBACK_URL_COOKIE = 'openid_fallback_url';
    private const int FALLBACK_URL_COOKIE_TTL_MINUTES = 10;
    private const string FALLBACK_URL = '/';

    /**
     * @param StartOpenIdRequest $request
     * @return JsonResponse|Response
     */
    public function auth(StartOpenIdRequest $request): JsonResponse|Response
    {
        $flow = $request->openidFlow();
        $service = resolve(OpenIdService::class);
        $sessionRequest = $request->input('request') ?: OpenIdSession::REQUEST_AUTH;

        $fund = $sessionRequest === OpenIdSession::REQUEST_FUND_REQUEST
            ? Fund::findOrFail($request->input('fund_id'))
            : null;

        if (!$flow) {
            return new Response(trans('requests.openid.not_enabled'), 403, [
                'Error-Code' => OpenIdException::ERROR_NOT_ENABLED,
            ]);
        }

        try {
            $authorization = $service->buildAuthorizationUrl($request->implementation(), $flow);
        } catch (OpenIdException) {
            return new Response(trans('requests.openid.unavailable'), 503, [
                'Error-Code' => OpenIdException::ERROR_UNKNOWN,
            ]);
        }

        $session = OpenIdSession::createSession(
            $request->implementation(),
            $flow,
            $request->client_type(),
            $request->input('target'),
            $authorization,
            $sessionRequest,
            $fund,
            $sessionRequest === OpenIdSession::REQUEST_FUND_REQUEST ? $request->auth_address() : null
        );

        return (new JsonResponse([
            'redirect_url' => $session->getRedirectUrl(),
        ]))->withCookie($this->makeFallbackUrlCookie($session->session_final_url));
    }

    /**
     * @param OpenIdSession $session
     * @return RedirectResponse
     */
    public function redirect(OpenIdSession $session): RedirectResponse
    {
        $flow = resolve(OpenIdService::class)->findSessionFlow($session);

        if (!$flow || !$session->implementation?->openidAvailable([$flow->provider])) {
            $session->markError();

            return $this->makeRedirectErrorResponse(
                $session->session_final_url,
                OpenIdException::ERROR_NOT_ENABLED
            );
        }

        return redirect($session->openid_auth_redirect_url);
    }

    /**
     * @param Request $request
     * @param string $provider
     * @return RedirectResponse
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        $state = $request->query('state');
        $service = resolve(OpenIdService::class);
        $fallbackUrl = $this->fallbackRedirectUrl($request);

        try {
            $session = $service->resolveCallbackSession($provider, is_string($state) ? $state : null);
        } catch (OpenIdException $exception) {
            $failedSession = $exception->getOpenIdSession();

            if ($failedSession?->isPending()) {
                $failedSession->markError();
            }

            return $this->makeRedirectErrorResponse(
                $failedSession?->session_final_url ?: $fallbackUrl,
                $exception->getOpenIdError() ?: OpenIdException::ERROR_SESSION_EXPIRED
            );
        }

        try {
            if ($session->session_request === OpenIdSession::REQUEST_AUTH) {
                return $this->makeAuthCallbackResponse(
                    $service->resolveBsnAuthIdentity($session, $request),
                    $session,
                    $request
                );
            }

            if ($session->session_request === OpenIdSession::REQUEST_FUND_REQUEST) {
                return $this->makeFundRequestCallbackResponse(
                    $session,
                    $service->resolveBsnFundRequest($session, $request)
                );
            }

            throw OpenIdException::withOpenIdError(
                OpenIdException::ERROR_UNKNOWN_SESSION_TYPE,
                'Unknown OpenID session request.',
                null,
                $session
            );
        } catch (OpenIdException $exception) {
            $session->markError();

            return $this->makeRedirectErrorResponse(
                $exception->getOpenIdSession()?->session_final_url ?: $session->session_final_url,
                $exception->getOpenIdError() ?: OpenIdException::ERROR_CALLBACK_FAILED
            );
        }
    }

    /**
     * @param Identity $identity
     * @param OpenIdSession $session
     * @param Request $request
     * @return RedirectResponse
     */
    protected function makeAuthCallbackResponse(
        Identity $identity,
        OpenIdSession $session,
        Request $request
    ): RedirectResponse {
        $proxy = Identity::makeAuthorizationShortTokenProxy();
        $identity->activateAuthorizationShortTokenProxy($proxy->exchange_token, $request->ip());
        $session->markResolved();

        $redirectUrl = rtrim($session->session_final_url ?: url('/'), '/') . '/auth-link';

        return redirect(url_extend_get_params($redirectUrl, [
            'token' => $proxy->exchange_token,
            ...($session->target !== null ? ['target' => $session->target] : []),
        ]))->withCookie($this->clearFallbackUrlCookie());
    }

    /**
     * @param OpenIdSession $session
     * @param string $success
     * @return RedirectResponse
     */
    protected function makeFundRequestCallbackResponse(OpenIdSession $session, string $success): RedirectResponse
    {
        $session->markResolved();

        return redirect(url_extend_get_params($session->session_final_url, [
            'openid_success' => $success,
        ]))->withCookie($this->clearFallbackUrlCookie());
    }

    /**
     * @param string $url
     * @param string $error
     * @return RedirectResponse
     */
    protected function makeRedirectErrorResponse(string $url, string $error): RedirectResponse
    {
        return redirect(url_extend_get_params($url, [
            'openid_error' => $error,
        ]))->withCookie($this->clearFallbackUrlCookie());
    }

    /**
     * @param string $url
     * @return SymfonyCookie
     */
    protected function makeFallbackUrlCookie(string $url): SymfonyCookie
    {
        return Cookie::make(
            static::FALLBACK_URL_COOKIE,
            Crypt::encryptString($url),
            static::FALLBACK_URL_COOKIE_TTL_MINUTES
        );
    }

    /**
     * @return SymfonyCookie
     */
    protected function clearFallbackUrlCookie(): SymfonyCookie
    {
        return Cookie::forget(static::FALLBACK_URL_COOKIE);
    }

    /**
     * @param Request $request
     * @return string
     */
    protected function fallbackRedirectUrl(Request $request): string
    {
        $fallbackUrl = url(static::FALLBACK_URL);
        $cookieFallbackUrl = $request->cookie(static::FALLBACK_URL_COOKIE);

        if (!$cookieFallbackUrl) {
            return $fallbackUrl;
        }

        try {
            return Crypt::decryptString($cookieFallbackUrl) ?: $fallbackUrl;
        } catch (DecryptException) {
            return $fallbackUrl;
        }
    }
}
