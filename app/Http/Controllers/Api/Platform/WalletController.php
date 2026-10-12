<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Wallets\StartWalletAuthRequest;
use App\Http\Requests\Api\Platform\Wallets\StartWalletDisclosureRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Responses\NoContentResponse;
use App\Models\Fund;
use App\Models\Identity;
use App\Models\IdentityEmail;
use App\Rules\IdentityEmailMaxRule;
use App\Rules\IdentityEmailUniqueRule;
use App\Services\WalletService\Exceptions\WalletWorkflowException;
use App\Services\WalletService\Models\WalletDisclosure;
use App\Services\WalletService\Models\WalletFlow;
use App\Services\WalletService\Models\WalletSession;
use App\Services\WalletService\Resources\WalletDisclosureResource;
use App\Services\WalletService\WalletDisclosureMapper;
use App\Services\WalletService\WalletService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Random\RandomException;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

class WalletController extends Controller
{
    private const string FALLBACK_URL_COOKIE = 'wallet_fallback_url';
    private const int FALLBACK_URL_COOKIE_TTL_MINUTES = 10;
    private const string FALLBACK_URL = '/';

    /**
     * @param StartWalletAuthRequest $request
     * @throws RandomException
     * @return JsonResponse|Response
     */
    public function auth(StartWalletAuthRequest $request): JsonResponse|Response
    {
        $sessionRequest = $request->input('request') ?: WalletSession::REQUEST_AUTH;

        $fund = $sessionRequest === WalletSession::REQUEST_FUND_REQUEST
            ? Fund::findOrFail($request->input('fund_id'))
            : null;

        return $this->startSession($request, $sessionRequest, $request->walletFlow(), $fund);
    }

    /**
     * @param StartWalletDisclosureRequest $request
     * @throws RandomException
     * @return JsonResponse|Response
     */
    public function disclosure(StartWalletDisclosureRequest $request): JsonResponse|Response
    {
        return $this->startSession($request, WalletSession::REQUEST_DISCLOSURE, $request->walletFlow());
    }

    /**
     * @param BaseFormRequest $request
     * @param Fund $fund
     * @throws RandomException
     * @return JsonResponse|Response
     */
    public function fundDisclosure(BaseFormRequest $request, Fund $fund): JsonResponse|Response
    {
        $this->authorize('store', [WalletDisclosure::class, $fund, $request->implementation(), $request->client_type()]);

        return $this->startSession(
            $request,
            WalletSession::REQUEST_DISCLOSURE,
            $fund->fund_config->wallet_disclosure_flow,
            $fund,
        );
    }

    /**
     * @param BaseFormRequest $request
     * @param Fund $fund
     * @param WalletDisclosure $walletDisclosure
     * @return JsonResponse
     */
    public function showDisclosure(
        BaseFormRequest $request,
        Fund $fund,
        WalletDisclosure $walletDisclosure,
    ): JsonResponse {
        $this->authorize('show', [$walletDisclosure, $fund, $request->implementation(), $request->client_type()]);

        resolve(WalletDisclosureMapper::class)->validateRecords($fund, $walletDisclosure->records);

        return WalletDisclosureResource::create($walletDisclosure)->response()->header('Cache-Control', 'no-store');
    }

    /**
     * @param BaseFormRequest $request
     * @param Fund $fund
     * @param WalletDisclosure $walletDisclosure
     * @return NoContentResponse
     */
    public function confirmDisclosureEmail(
        BaseFormRequest $request,
        Fund $fund,
        WalletDisclosure $walletDisclosure,
    ): NoContentResponse {
        $this->authorize('show', [$walletDisclosure, $fund, $request->implementation(), $request->client_type()]);
        $this->authorize('create', [IdentityEmail::class, $request->identityProxy2FAConfirmed()]);

        DB::transaction(function () use ($request, $fund, $walletDisclosure): void {
            $identity = Identity::whereKey($request->auth_id())->lockForUpdate()->firstOrFail();
            $walletDisclosure = WalletDisclosure::whereKey($walletDisclosure->id)->lockForUpdate()->firstOrFail();

            Gate::forUser($identity)->authorize('show', [
                $walletDisclosure, $fund, $request->implementation(), $request->client_type(),
            ]);
            Gate::forUser($identity)->authorize('create', [IdentityEmail::class, $request->identityProxy2FAConfirmed()]);

            $email = $walletDisclosure->email;
            $primaryEmail = $identity->primary_email;

            if ($email !== null && $primaryEmail?->verified && $primaryEmail->email === $email) {
                return;
            }

            if ($primaryEmail || Validator::make(['email' => $email], [
                'email' => [
                    'bail', 'required', ...$request->emailRules(),
                    new IdentityEmailUniqueRule(lockForUpdate: true), new IdentityEmailMaxRule($identity->address),
                ],
            ])->fails()) {
                throw ValidationException::withMessages(['email' => trans('wallets.disclosure.email_unavailable')]);
            }

            $identity->addEmail($email)->setVerified();
        }, 3);

        return new NoContentResponse();
    }

    /**
     * @param WalletSession $session
     * @return RedirectResponse
     */
    public function redirect(WalletSession $session): RedirectResponse
    {
        $flow = resolve(WalletService::class)->findSessionFlow($session);

        $available = $session->session_request === WalletSession::REQUEST_DISCLOSURE
            ? WalletService::disclosureAvailable($session->implementation)
            : $session->implementation?->walletAvailable($flow ? [$flow->provider] : null);

        if (!$flow || !$available) {
            $session->markError();

            return $this->makeRedirectErrorResponse(
                $session->session_final_url,
                WalletWorkflowException::ERROR_NOT_ENABLED
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
        $service = resolve(WalletService::class);
        $fallbackUrl = $this->fallbackRedirectUrl($request);

        try {
            $session = $service->resolveCallbackSession($provider, is_string($state) ? $state : null);
        } catch (WalletWorkflowException $exception) {
            $failedSession = $exception->session();

            if ($failedSession?->isPending()) {
                $failedSession->markError();
            }

            return $this->makeRedirectErrorResponse(
                $failedSession?->session_final_url ?: $fallbackUrl,
                $exception->errorCode() ?: WalletWorkflowException::ERROR_SESSION_EXPIRED
            );
        }

        return redirect($session->implementation->urlWebshop('/auth-link') . '#' . http_build_query([
            'wallet_session' => $session->session_uid,
            'wallet_ticket' => Crypt::encryptString(json_encode([
                'session_uid' => $session->session_uid,
                'provider' => $provider,
                'query' => $request->query(),
            ], JSON_THROW_ON_ERROR)),
        ]))->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    /**
     * @param Request $request
     * @param WalletSession $session
     * @return JsonResponse
     */
    public function complete(Request $request, WalletSession $session): JsonResponse
    {
        $data = $request->validate([
            'browser_token' => 'required|string|max:128',
            'callback_ticket' => 'required|string|max:20000',
        ]);

        try {
            $ticket = json_decode(Crypt::decryptString($data['callback_ticket']), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException) {
            abort(403);
        }

        abort_unless($ticket['session_uid'] === $session->session_uid, 403);

        return DB::transaction(function () use ($request, $session, $data, $ticket) {
            $session = WalletSession::whereKey($session->id)->lockForUpdate()->firstOrFail();

            abort_unless(hash_equals($session->browser_token_hash ?? '', hash('sha256', $data['browser_token'])), 403);
            abort_unless($session->isPending() && !$session->isExpired(), 403);

            if ($session->session_request === WalletSession::REQUEST_DISCLOSURE) {
                abort_unless($request->user() instanceof Identity &&
                    $request->user()->address === $session->identity_address, 403);
            }

            $callback = Request::create(
                '/api/v1/platform/wallets/' . $ticket['provider'] . '/callback',
                'GET',
                $ticket['query']
            );
            $callback->server->set('REMOTE_ADDR', $request->ip());
            $response = $this->finishCallback($callback, $session, $ticket['provider']);

            return (new JsonResponse(['redirect_url' => $response->getTargetUrl()]))
                ->withCookie($this->clearFallbackUrlCookie())
                ->header('Cache-Control', 'no-store');
        });
    }

    /**
     * @param BaseFormRequest $request
     * @param string $sessionRequest
     * @param WalletFlow|null $flow
     * @param Fund|null $fund
     * @throws RandomException
     * @return JsonResponse|Response
     */
    protected function startSession(
        BaseFormRequest $request,
        string $sessionRequest,
        ?WalletFlow $flow,
        ?Fund $fund = null,
    ): JsonResponse|Response {
        $service = resolve(WalletService::class);

        if (!$flow) {
            return new Response(trans('requests.wallets.not_enabled'), 403, [
                'Error-Code' => WalletWorkflowException::ERROR_NOT_ENABLED,
            ]);
        }

        try {
            $authorization = $service->buildAuthorizationUrl($request->implementation(), $flow);
        } catch (WalletWorkflowException) {
            return new Response(trans('requests.wallets.unavailable'), 503, [
                'Error-Code' => WalletWorkflowException::ERROR_UNKNOWN,
            ]);
        }

        $browserToken = bin2hex(random_bytes(32));

        $session = WalletSession::createSession(
            $request->implementation(),
            $flow,
            $request->client_type(),
            $sessionRequest === WalletSession::REQUEST_AUTH ? $request->input('target') : null,
            $authorization,
            $sessionRequest,
            $fund,
            $sessionRequest !== WalletSession::REQUEST_AUTH ? $request->auth_address() : null,
            $browserToken
        );

        return (new JsonResponse([
            'redirect_url' => $session->getRedirectUrl(),
            'session_uid' => $session->session_uid,
            'browser_token' => $browserToken,
        ], headers: ['Cache-Control' => 'no-store']))
            ->withCookie($this->makeFallbackUrlCookie($session->session_final_url));
    }

    /**
     * @param Request $request
     * @param WalletSession $session
     * @param string $provider
     * @return RedirectResponse
     */
    protected function finishCallback(Request $request, WalletSession $session, string $provider): RedirectResponse
    {
        $service = resolve(WalletService::class);

        try {
            $service->resolveCallbackSession($provider, $session->state);

            if ($session->session_request === WalletSession::REQUEST_DISCLOSURE) {
                $fund = isset($session->meta['fund_id']) ? Fund::find($session->meta['fund_id']) : null;

                if (isset($session->meta['fund_id']) && (!$fund ||
                    $session->wallet_flow_id !== $fund->fund_config?->wallet_disclosure_flow_id ||
                    Gate::forUser($session->sessionIdentity())->denies('store', [
                        WalletDisclosure::class, $fund, $session->implementation, $session->client_type,
                    ]))) {
                    throw WalletWorkflowException::withError(WalletWorkflowException::ERROR_NOT_ENABLED);
                }

                $payload = $service->resolveDisclosure($session, $request);
                $disclosure = null;

                if ($fund) {
                    $records = resolve(WalletDisclosureMapper::class)->map($fund, $payload);
                    $verifiedAt = now();

                    $disclosure = WalletDisclosure::create([
                        'wallet_session_id' => $session->id,
                        'wallet_flow_id' => $session->wallet_flow_id,
                        'identity_id' => $session->sessionIdentity()->id,
                        'fund_id' => $fund->id,
                        'payload' => $payload,
                        'records' => $records,
                        'verified_at' => $verifiedAt,
                        'expires_at' => $verifiedAt->copy()->addMinutes(30),
                    ]);
                }

                $session->markResolved();

                return redirect(url_extend_get_params($session->session_final_url, [
                    'disclosure_success' => 1,
                    ...$disclosure ? ['wallet_disclosure' => $disclosure->id] : [],
                ]))->withCookie($this->clearFallbackUrlCookie());
            }

            if ($session->session_request === WalletSession::REQUEST_AUTH) {
                return $this->makeAuthCallbackResponse(
                    $service->resolveBsnAuthIdentity($session, $request),
                    $session,
                    $request
                );
            }

            if ($session->session_request === WalletSession::REQUEST_FUND_REQUEST) {
                return $this->makeFundRequestCallbackResponse(
                    $session,
                    $service->resolveBsnFundRequest($session, $request)
                );
            }

            throw WalletWorkflowException::withError(
                WalletWorkflowException::ERROR_UNKNOWN_SESSION_TYPE,
                'Unknown Wallet session request.',
                null,
                $session
            );
        } catch (ValidationException) {
            $session->markError();

            return $this->makeRedirectErrorResponse(
                $session->session_final_url,
                WalletWorkflowException::ERROR_DISCLOSURE_INVALID,
            );
        } catch (WalletWorkflowException $exception) {
            $session->markError();

            return $this->makeRedirectErrorResponse(
                $exception->session()?->session_final_url ?: $session->session_final_url,
                $exception->errorCode() ?: WalletWorkflowException::ERROR_CALLBACK_FAILED
            );
        }
    }

    /**
     * @param Identity $identity
     * @param WalletSession $session
     * @param Request $request
     * @return RedirectResponse
     */
    protected function makeAuthCallbackResponse(
        Identity $identity,
        WalletSession $session,
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
     * @param WalletSession $session
     * @param string $success
     * @return RedirectResponse
     */
    protected function makeFundRequestCallbackResponse(WalletSession $session, string $success): RedirectResponse
    {
        $session->markResolved();

        return redirect(url_extend_get_params($session->session_final_url, [
            'wallet_success' => $success,
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
            'wallet_error' => $error,
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
