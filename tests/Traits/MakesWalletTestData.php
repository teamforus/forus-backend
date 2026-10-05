<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\Identity;
use App\Models\Implementation;
use App\Services\WalletService\Exceptions\WalletWorkflowException;
use App\Services\WalletService\Models\WalletFlow;
use App\Services\WalletService\Models\WalletSession;
use App\Services\WalletService\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

trait MakesWalletTestData
{
    use MakesTestOrganizations;

    public const string FAKE_REDIRECT_URL = 'https://openid.example/authorize';
    public const string FAKE_STATE = 'openid-state';
    public const string FAKE_NONCE = 'openid-nonce';
    public const string FAKE_CODE_VERIFIER = 'openid-code-verifier';
    public const string FAKE_FLOW_KEY = 'nl_wallet';
    protected const string WALLET_BROWSER_TOKEN = 'test-browser-token';

    protected const string WALLET_FALLBACK_COOKIE = 'wallet_fallback_url';

    /**
     * @param array $overrides
     * @return array
     */
    protected function makeWalletContext(array $overrides = []): array
    {
        return [
            'issuer' => 'https://issuer.example',
            'client_id' => 'verid-client',
            'client_secret' => 'verid-secret',
            'redirect_url' => '/api/v1/platform/wallets/verid/callback',
            'scopes' => ['openid', 'nin'],
            'bsn_claim' => 'nin.identifier',
            'bsn_claim_source' => 'claims',
            'auth_params' => ['prompt' => 'login'],
            'code_challenge_method' => 'S256',
            'id_token_signed_response_alg' => 'ES384',
            'token_endpoint_auth_method' => 'client_secret_basic',
            ...$overrides,
        ];
    }

    /**
     * @param array $data
     * @return WalletFlow
     */
    protected function makeWalletFlow(array $data = []): WalletFlow
    {
        $provider = $data['provider'] ?? WalletService::PROVIDER_VERID;
        $key = $data['key'] ?? static::FAKE_FLOW_KEY;

        $flow = WalletFlow::query()->updateOrCreate([
            'provider' => $provider,
            'type' => WalletFlow::TYPE_AUTHENTICATION,
            'key' => $key,
        ], [
            'context' => array_key_exists('context', $data) ? $data['context'] : $this->makeWalletContext(),
            'name' => $data['name'] ?? 'NL Wallet',
        ]);

        return $flow->refresh();
    }

    /**
     * @param array $implementationData
     * @param array $organizationData
     * @param WalletFlow|null $walletFlow
     * @return Implementation
     */
    protected function makeWalletImplementation(
        array $implementationData = [],
        array $organizationData = [],
        ?WalletFlow $walletFlow = null,
    ): Implementation {
        $walletFlow ??= $this->makeWalletFlow();

        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_openid' => true,
            'bsn_enabled' => true,
            ...$organizationData,
        ]);

        $implementation = $this->makeTestImplementation($organization, [
            'openid_enabled' => true,
            ...$implementationData,
        ]);

        if ($implementationData) {
            $implementation->forceFill($implementationData)->save();
        }

        $implementation->wallet_flows()->syncWithoutDetaching([$walletFlow->id]);

        return $implementation->refresh();
    }

    /**
     * @param array $overrides
     * @return array
     */
    protected function makeWalletAuthorization(array $overrides = []): array
    {
        return [
            'redirect_url' => static::FAKE_REDIRECT_URL,
            'state' => token_generator()->generate(40),
            'nonce' => static::FAKE_NONCE,
            'code_verifier' => static::FAKE_CODE_VERIFIER,
            'meta' => [],
            ...$overrides,
        ];
    }

    /**
     * @param Implementation $implementation
     * @param array $data
     * @param Fund|null $fund
     * @param Identity|null $identity
     * @return WalletSession
     */
    protected function makeWalletSession(
        Implementation $implementation,
        array $data = [],
        ?Fund $fund = null,
        ?Identity $identity = null,
    ): WalletSession {
        $sessionRequest = $data['session_request'] ?? (
            $fund ? WalletSession::REQUEST_FUND_REQUEST : WalletSession::REQUEST_AUTH
        );
        $authorization = $data['authorization'] ?? $this->makeWalletAuthorization();
        unset($data['authorization']);

        /** @var WalletFlow $flow */
        $flow = $data['wallet_flow'] ?? $implementation
            ->availableWalletFlowsForProvider($data['provider'] ?? WalletService::PROVIDER_VERID)
            ->first();
        unset($data['wallet_flow'], $data['provider']);

        $session = WalletSession::createSession(
            $implementation,
            $flow,
            $data['client_type'] ?? Implementation::FRONTEND_WEBSHOP,
            $data['target'] ?? null,
            $authorization,
            $sessionRequest,
            $fund,
            $data['identity_address'] ?? $identity?->address,
            static::WALLET_BROWSER_TOKEN,
        );

        if ($data) {
            $session->forceFill($data)->save();
        }

        return $session->refresh();
    }

    /**
     * @param string $provider
     * @param array $query
     * @param string|null $fallbackUrl
     * @param bool $complete
     * @return TestResponse
     */
    protected function walletCallbackRequest(
        string $provider = WalletService::PROVIDER_VERID,
        array $query = [],
        ?string $fallbackUrl = null,
        bool $complete = true,
    ): TestResponse {
        $url = sprintf('/api/v1/platform/wallets/%s/callback', $provider);

        if ($query) {
            $url .= '?' . http_build_query($query);
        }

        $response = ($fallbackUrl ? $this->withUnencryptedCookie(
            self::WALLET_FALLBACK_COOKIE,
            Crypt::encryptString($fallbackUrl),
        ) : $this)->get($url);

        parse_str(Str::afterLast($response->headers->get('Location', ''), '#'), $callback);

        if ($complete && isset($callback['wallet_ticket'])) {
            return $this->apiCompleteWalletAuthRequest($callback['wallet_session'], [
                'browser_token' => static::WALLET_BROWSER_TOKEN,
                'callback_ticket' => $callback['wallet_ticket'],
            ]);
        }

        return $response;
    }

    /**
     * @param string|null $callbackBsn
     * @param array|null $callbackPayload
     * @param WalletWorkflowException|null $authorizationException
     * @param WalletWorkflowException|null $callbackException
     * @param array $authorizationData
     * @return void
     */
    protected function fakeWalletService(
        ?string $callbackBsn = null,
        ?array $callbackPayload = null,
        ?WalletWorkflowException $authorizationException = null,
        ?WalletWorkflowException $callbackException = null,
        array $authorizationData = [],
    ): void {
        $authorization = $this->makeWalletAuthorization([
            'state' => static::FAKE_STATE,
            ...$authorizationData,
        ]);

        $this->app->instance(WalletService::class, new class (
            $authorization,
            $callbackPayload,
            $callbackBsn,
            $authorizationException,
            $callbackException,
        ) extends WalletService {
            /**
             * @param array $authorization
             * @param array|null $callbackPayload
             * @param string|null $callbackBsn
             * @param WalletWorkflowException|null $authorizationException
             * @param WalletWorkflowException|null $callbackException
             */
            public function __construct(
                private readonly array $authorization,
                private readonly ?array $callbackPayload,
                private readonly ?string $callbackBsn,
                private readonly ?WalletWorkflowException $authorizationException,
                private readonly ?WalletWorkflowException $callbackException,
            ) {
            }

            /**
             * @param Implementation $implementation
             * @param WalletFlow $flow
             * @return array
             */
            public function buildAuthorizationUrl(Implementation $implementation, WalletFlow $flow): array
            {
                if ($this->authorizationException) {
                    throw $this->authorizationException;
                }

                return [
                    ...$this->authorization,
                    'meta' => [
                        ...($this->authorization['meta'] ?? []),
                    ],
                ];
            }

            /**
             * @param string $provider
             * @param WalletSession $session
             * @param Request $request
             * @return array
             */
            public function resolveCallback(string $provider, WalletSession $session, Request $request): array
            {
                if ($this->callbackException) {
                    throw $this->callbackException;
                }

                return $this->callbackPayload ?: [
                    'claims' => [
                        'nin' => [
                            'identifier' => $this->callbackBsn ?: '999994542',
                        ],
                    ],
                    'id_token' => 'openid-id-token',
                    'access_token' => 'openid-access-token',
                ];
            }
        });
    }

    /**
     * @return void
     */
    protected function fakeFailingWalletService(): void
    {
        $this->fakeWalletService(authorizationException: new WalletWorkflowException('Unable to build authorization URL.'));
    }
}
