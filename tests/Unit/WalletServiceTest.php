<?php

namespace Tests\Unit;

use App\Services\OpenIdService\OpenIdClientService;
use App\Services\WalletService\Exceptions\WalletWorkflowException;
use App\Services\WalletService\Models\WalletFlow;
use App\Services\WalletService\Models\WalletSession;
use App\Services\WalletService\VerId\VerIdIntentCreationException;
use App\Services\WalletService\WalletService;
use Facile\JoseVerifier\JWK\MemoryJwksProvider;
use Facile\OpenIDClient\Client\Client;
use Facile\OpenIDClient\Client\ClientInterface;
use Facile\OpenIDClient\Client\Metadata\ClientMetadata;
use Facile\OpenIDClient\Issuer\Issuer;
use Facile\OpenIDClient\Issuer\Metadata\IssuerMetadata;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesWalletTestData;

class WalletServiceTest extends TestCase
{
    use DatabaseTransactions;
    use MakesWalletTestData;
    use MakesTestFunds;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('openid.enabled', true);
    }

    /**
     * @throws WalletWorkflowException
     * @return void
     */
    public function testProviderConfigUsesFlowContextOnly(): void
    {
        Config::set('openid.providers', [
            WalletService::PROVIDER_VERID => [
                'issuer' => 'https://wrong.example',
                'client_id' => 'wrong-client',
                'client_secret' => 'wrong-secret',
                'redirect_url' => '/wrong/callback',
                'scopes' => ['wrong'],
                'bsn_claim' => 'wrong.claim',
                'bsn_claim_source' => 'claims',
                'auth_params' => ['prompt' => 'wrong'],
                'code_challenge_method' => 'plain',
                'id_token_signed_response_alg' => 'RS256',
                'token_endpoint_auth_method' => 'client_secret_post',
            ],
        ]);

        $config = (new WalletService())->getProviderConfig(
            $this->makeWalletFlow(['context' => $this->makeVeridContext()])
        );

        $this->assertSame('https://issuer.example', $config['issuer']);
        $this->assertSame('verid-client', $config['client_id']);
        $this->assertSame('verid-secret', $config['client_secret']);
        $this->assertSame('/api/v1/platform/wallets/verid/callback', $config['redirect_url']);
        $this->assertSame(['openid', 'nin'], $config['scopes']);
        $this->assertSame('nin.identifier', $config['bsn_claim']);
        $this->assertSame('claims', $config['bsn_claim_source']);
        $this->assertSame(['prompt' => 'login'], $config['auth_params']);
        $this->assertSame('S256', $config['code_challenge_method']);
        $this->assertSame('ES384', $config['id_token_signed_response_alg']);
        $this->assertSame('client_secret_basic', $config['token_endpoint_auth_method']);
    }

    /**
     * @throws WalletWorkflowException
     * @return void
     */
    public function testProviderConfigNormalizesScopesAndAuthParams(): void
    {
        $config = (new WalletService())->getProviderConfig(
            $this->makeWalletFlow([
                'context' => $this->makeVeridContext([
                    'scopes' => ['openid', '', 'nin'],
                    'auth_params' => 'prompt=login',
                ]),
            ])
        );

        $this->assertSame(['openid', 'nin'], $config['scopes']);
        $this->assertSame([], $config['auth_params']);
    }

    /**
     * @return void
     */
    public function testProviderContextRequiresFullRuntimeConfig(): void
    {
        $context = $this->makeVeridContext();

        $this->assertTrue(WalletService::providerConfigured(WalletService::PROVIDER_VERID));
        $this->assertFalse(WalletService::providerConfigured('unknown'));
        $this->assertTrue(WalletService::providerContextConfigured(WalletService::PROVIDER_VERID, $context));

        $this->assertFalse(WalletService::providerContextConfigured(
            WalletService::PROVIDER_VERID,
            [...$context, 'bsn_claim' => null],
        ));

        $this->assertFalse(WalletService::providerContextConfigured(
            WalletService::PROVIDER_VERID,
            $this->makeVeridContext(['scopes' => 'openid nin'])
        ));

        $this->assertFalse(WalletService::providerContextConfigured(
            WalletService::PROVIDER_VERID,
            $this->makeVeridContext(['scopes' => ['']])
        ));

        $this->assertFalse(WalletService::providerContextConfigured(
            WalletService::PROVIDER_VERID,
            $this->makeVeridContext(['bsn_claim_source' => 'user_info'])
        ));

        $this->assertFalse(WalletService::providerContextConfigured(
            WalletService::PROVIDER_VERID,
            $this->makeVeridContext(['issuer' => ['https://issuer.example']])
        ));
    }

    /**
     * @throws WalletWorkflowException
     * @return void
     */
    public function testBuildAuthorizationUrlCreatesVeridIntentWhenEnabled(): void
    {
        Http::fake([
            'https://issuer.example/intent' => Http::response([
                'intent_id' => 'intent-123',
            ]),
        ]);

        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', 'openid-code-verifier', true)), '+/', '-_'), '=');
        $service = $this->makeWalletServiceWithClient($this->makeOpenIdClient());
        $flow = $this->makeWalletFlow(['context' => $this->makeVeridContext()]);
        $implementation = $this->makeWalletImplementation([
            'openid_verid_brand_uuid' => '00000000-0000-0000-0000-000000000001',
        ], walletFlow: $flow);

        $authorization = $service->buildAuthorizationUrl($implementation, $flow);

        parse_str((string) parse_url($authorization['redirect_url'], PHP_URL_QUERY), $query);

        $this->assertSame('intent-123', $query['intent_id']);
        $this->assertSame('intent-123', $authorization['meta']['intent_id']);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://issuer.example/intent' &&
            $request->method() === 'POST' &&
            $request->hasHeader('Authorization', 'Basic ' . base64_encode('verid-client:verid-secret')) &&
            $request->data() === [
                'scope' => 'openid',
                'client_id' => 'verid-client',
                'code_challenge' => $codeChallenge,
                'brandUuid' => '00000000-0000-0000-0000-000000000001',
            ]);
    }

    /**
     * @return void
     */
    public function testBuildAuthorizationUrlFailsWhenVeridIntentFails(): void
    {
        Http::fake([
            'https://issuer.example/intent' => Http::response([
                'error' => 'invalid_request',
                'error_description' => 'Invalid brand.',
            ], 400),
        ]);

        try {
            $flow = $this->makeWalletFlow(['context' => $this->makeVeridContext()]);
            $implementation = $this->makeWalletImplementation([
                'openid_verid_brand_uuid' => '00000000-0000-0000-0000-000000000001',
            ], walletFlow: $flow);

            $this->makeWalletServiceWithClient($this->makeOpenIdClient())
                ->buildAuthorizationUrl($implementation, $flow);

            $this->fail('Expected WalletWorkflowException.');
        } catch (WalletWorkflowException $exception) {
            $this->assertInstanceOf(VerIdIntentCreationException::class, $exception->getPrevious());
        }
    }

    /**
     * @return void
     */
    public function testProviderAvailabilityDependsOnGlobalFlagAndImplementationState(): void
    {
        $implementation = $this->makeWalletImplementation();

        $this->assertTrue(WalletService::providerEnabled(WalletService::PROVIDER_VERID, $implementation));
        $this->assertSame([WalletService::PROVIDER_VERID], WalletService::enabledProviderKeys($implementation));

        Config::set('openid.enabled', false);

        $this->assertFalse(WalletService::providerEnabled(WalletService::PROVIDER_VERID, $implementation));
        $this->assertSame([], WalletService::enabledProviderKeys($implementation));

        Config::set('openid.enabled', true);
        $implementation->forceFill(['openid_enabled' => false])->save();

        $this->assertFalse(WalletService::providerEnabled(WalletService::PROVIDER_VERID, $implementation->refresh()));
        $this->assertSame([], WalletService::enabledProviderKeys($implementation));

        $implementation = $this->makeWalletImplementation(walletFlow: $this->makeWalletFlow([
            'key' => 'datakeeper',
            'context' => null,
        ]));

        $this->assertFalse(WalletService::providerEnabled(WalletService::PROVIDER_VERID, $implementation));

        $implementation = $this->makeWalletImplementation(organizationData: [
            'allow_openid' => false,
        ]);

        $this->assertFalse(WalletService::providerEnabled(WalletService::PROVIDER_VERID, $implementation));
    }

    /**
     * @throws WalletWorkflowException
     * @return void
     */
    public function testResolveBsnFromPayloadExtractsDigitOnlyClaim(): void
    {
        $bsn = (new WalletService())->resolveBsnFromPayload(
            [
                'claims' => [
                    'nin' => [
                        'identifier' => '569657222',
                    ],
                ],
            ],
            $this->makeWalletFlow(['context' => $this->makeVeridContext()])
        );

        $this->assertSame('569657222', $bsn);
    }

    /**
     * @return void
     */
    public function testResolveBsnFromPayloadRejectsInvalidClaims(): void
    {
        $flow = $this->makeWalletFlow(['context' => $this->makeVeridContext()]);

        $this->assertBsnPayloadWalletError([], $flow);
        $this->assertBsnPayloadWalletError(['claims' => ['nin' => ['identifier' => 569657222]]], $flow);
        $this->assertBsnPayloadWalletError(['claims' => ['nin' => ['identifier' => '12345']]], $flow);
        $this->assertBsnPayloadWalletError(['claims' => ['nin' => ['identifier' => '569.657.222']]], $flow);
        $this->assertBsnPayloadWalletError(['claims' => ['nin' => ['identifier' => '569 657 222']]], $flow);
        $this->assertBsnPayloadWalletError(['claims' => ['nin' => ['identifier' => '569-657-222']]], $flow);
    }

    /**
     * @throws WalletWorkflowException
     * @return void
     */
    public function testResolveCallbackSessionReturnsPendingSession(): void
    {
        $session = $this->makeWalletSession($this->makeWalletImplementation());

        $resolvedSession = (new WalletService())->resolveCallbackSession(
            WalletService::PROVIDER_VERID,
            $session->state,
        );

        $this->assertSame($session->id, $resolvedSession->id);
    }

    /**
     * @return void
     */
    public function testResolveCallbackSessionRejectsMismatchedProvider(): void
    {
        $implementation = $this->makeWalletImplementation();
        $session = $this->makeWalletSession($implementation, [
            'wallet_flow' => $this->makeWalletFlow([
                'provider' => 'other',
                'key' => 'other_wallet',
            ]),
        ]);

        try {
            (new WalletService())->resolveCallbackSession(WalletService::PROVIDER_VERID, $session->state);
            $this->fail('Expected WalletWorkflowException.');
        } catch (WalletWorkflowException $exception) {
            $this->assertSame(WalletWorkflowException::ERROR_SESSION_EXPIRED, $exception->errorCode());
            $this->assertNull($exception->session());
            $this->assertSame(WalletSession::STATE_PENDING, $session->refresh()->session_state);
        }
    }

    /**
     * @return void
     */
    public function testResolveCallbackSessionRejectsWhenSessionFlowIsDisabled(): void
    {
        $implementation = $this->makeWalletImplementation();
        $session = $this->makeWalletSession($implementation);

        $implementation->wallet_flows()->detach();

        try {
            (new WalletService())->resolveCallbackSession(WalletService::PROVIDER_VERID, $session->state);
            $this->fail('Expected WalletWorkflowException.');
        } catch (WalletWorkflowException $exception) {
            $this->assertSame('not_enabled', $exception->errorCode());
            $this->assertSame($session->id, $exception->session()?->id);
        }
    }

    /**
     * @return void
     */
    public function testResolveCallbackSessionMapsDisabledGlobalConfigWithMatchingSession(): void
    {
        $session = $this->makeWalletSession($this->makeWalletImplementation());

        Config::set('openid.enabled', false);

        try {
            (new WalletService())->resolveCallbackSession(WalletService::PROVIDER_VERID, $session->state);
            $this->fail('Expected WalletWorkflowException.');
        } catch (WalletWorkflowException $exception) {
            $this->assertSame('not_enabled', $exception->errorCode());
            $this->assertSame($session->id, $exception->session()?->id);
        }
    }

    /**
     * @return void
     */
    public function testResolveCallbackSessionExpiresOldPendingSession(): void
    {
        $session = $this->makeWalletSession($this->makeWalletImplementation(), [
            'created_at' => now()->subSeconds(Config::get('openid.session_expiration_seconds') + 1),
        ]);

        try {
            (new WalletService())->resolveCallbackSession(WalletService::PROVIDER_VERID, $session->state);
            $this->fail('Expected WalletWorkflowException.');
        } catch (WalletWorkflowException $exception) {
            $this->assertSame('session_expired', $exception->errorCode());
            $this->assertSame($session->id, $exception->session()?->id);
            $this->assertSame(WalletSession::STATE_EXPIRED, $session->refresh()->session_state);
        }
    }

    /**
     * @return void
     */
    public function testResolveCallbackSessionRejectsNonPendingSession(): void
    {
        $session = $this->makeWalletSession($this->makeWalletImplementation(), [
            'session_state' => WalletSession::STATE_RESOLVED,
        ]);

        try {
            (new WalletService())->resolveCallbackSession(WalletService::PROVIDER_VERID, $session->state);
            $this->fail('Expected WalletWorkflowException.');
        } catch (WalletWorkflowException $exception) {
            $this->assertSame('session_expired', $exception->errorCode());
            $this->assertSame($session->id, $exception->session()?->id);
            $this->assertSame(WalletSession::STATE_RESOLVED, $session->refresh()->session_state);
        }
    }

    /**
     * @param array $payload
     * @param WalletFlow $flow
     * @return void
     */
    protected function assertBsnPayloadWalletError(array $payload, WalletFlow $flow): void
    {
        try {
            (new WalletService())->resolveBsnFromPayload($payload, $flow);
            $this->fail('Expected WalletWorkflowException.');
        } catch (WalletWorkflowException $exception) {
            $this->assertSame('missing_claims', $exception->errorCode());
        }
    }

    /**
     * @param ClientInterface $client
     * @return WalletService
     */
    protected function makeWalletServiceWithClient(ClientInterface $client): WalletService
    {
        $this->app->instance(OpenIdClientService::class, new class ($client) extends OpenIdClientService {
            /**
             * @param ClientInterface $client
             */
            public function __construct(protected ClientInterface $client)
            {
            }

            /**
             * @param array $config
             * @return ClientInterface
             */
            public function makeClient(array $config): ClientInterface
            {
                return $this->client;
            }

            /**
             * @return string
             */
            protected function makeRandomToken(): string
            {
                return 'openid-random-token';
            }

            /**
             * @return string
             */
            protected function makeCodeVerifier(): string
            {
                return 'openid-code-verifier';
            }
        });

        return new WalletService();
    }

    /**
     * @param string|null $intentEndpoint
     * @return ClientInterface
     */
    protected function makeOpenIdClient(?string $intentEndpoint = 'https://issuer.example/intent'): ClientInterface
    {
        return new Client(
            new Issuer(IssuerMetadata::fromArray(array_filter([
                'issuer' => 'https://issuer.example',
                'authorization_endpoint' => 'https://issuer.example/authorize',
                'jwks_uri' => 'https://issuer.example/jwks',
                'intent_endpoint' => $intentEndpoint,
            ], static fn ($value) => $value !== null)), new MemoryJwksProvider()),
            ClientMetadata::fromArray([
                'client_id' => 'verid-client',
                'client_secret' => 'verid-secret',
                'redirect_uris' => [
                    url('/api/v1/platform/wallets/verid/callback'),
                ],
                'id_token_signed_response_alg' => 'ES384',
                'token_endpoint_auth_method' => 'client_secret_basic',
            ]),
        );
    }

    /**
     * @param array $overrides
     * @return array
     */
    protected function makeVeridContext(array $overrides = []): array
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
}
