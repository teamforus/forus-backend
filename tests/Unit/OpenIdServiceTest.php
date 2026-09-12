<?php

namespace Tests\Unit;

use App\Models\Implementation;
use App\Services\OpenIdService\Models\OpenIdFlow;
use App\Services\OpenIdService\Models\OpenIdSession;
use App\Services\OpenIdService\OpenIdException;
use App\Services\OpenIdService\OpenIdService;
use App\Services\OpenIdService\VerId\VerIdIntentCreationException;
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
use Tests\Traits\MakesOpenIdTestData;
use Tests\Traits\MakesTestFunds;

class OpenIdServiceTest extends TestCase
{
    use DatabaseTransactions;
    use MakesOpenIdTestData;
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
     * @throws OpenIdException
     * @return void
     */
    public function testProviderConfigUsesFlowContextOnly(): void
    {
        Config::set('openid.providers', [
            OpenIdService::PROVIDER_VERID => [
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

        $config = (new OpenIdService())->getProviderConfig(
            $this->makeOpenIdFlow(['context' => $this->makeVeridContext()])
        );

        $this->assertSame('https://issuer.example', $config['issuer']);
        $this->assertSame('verid-client', $config['client_id']);
        $this->assertSame('verid-secret', $config['client_secret']);
        $this->assertSame('/api/v1/platform/openid/verid/callback', $config['redirect_url']);
        $this->assertSame(['openid', 'nin'], $config['scopes']);
        $this->assertSame('nin.identifier', $config['bsn_claim']);
        $this->assertSame('claims', $config['bsn_claim_source']);
        $this->assertSame(['prompt' => 'login'], $config['auth_params']);
        $this->assertSame('S256', $config['code_challenge_method']);
        $this->assertSame('ES384', $config['id_token_signed_response_alg']);
        $this->assertSame('client_secret_basic', $config['token_endpoint_auth_method']);
    }

    /**
     * @throws OpenIdException
     * @return void
     */
    public function testProviderConfigNormalizesScopesAndAuthParams(): void
    {
        $config = (new OpenIdService())->getProviderConfig(
            $this->makeOpenIdFlow([
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

        $this->assertTrue(OpenIdService::providerConfigured(OpenIdService::PROVIDER_VERID));
        $this->assertFalse(OpenIdService::providerConfigured('unknown'));
        $this->assertTrue(OpenIdService::providerContextConfigured(OpenIdService::PROVIDER_VERID, $context));

        $this->assertFalse(OpenIdService::providerContextConfigured(
            OpenIdService::PROVIDER_VERID,
            [...$context, 'bsn_claim' => null],
        ));

        $this->assertFalse(OpenIdService::providerContextConfigured(
            OpenIdService::PROVIDER_VERID,
            $this->makeVeridContext(['scopes' => 'openid nin'])
        ));

        $this->assertFalse(OpenIdService::providerContextConfigured(
            OpenIdService::PROVIDER_VERID,
            $this->makeVeridContext(['scopes' => ['']])
        ));

        $this->assertFalse(OpenIdService::providerContextConfigured(
            OpenIdService::PROVIDER_VERID,
            $this->makeVeridContext(['bsn_claim_source' => 'user_info'])
        ));

        $this->assertFalse(OpenIdService::providerContextConfigured(
            OpenIdService::PROVIDER_VERID,
            $this->makeVeridContext(['issuer' => ['https://issuer.example']])
        ));
    }

    /**
     * @throws OpenIdException
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
        $service = $this->makeOpenIdServiceWithClient($this->makeOpenIdClient());
        $flow = $this->makeOpenIdFlow(['context' => $this->makeVeridContext()]);
        $implementation = $this->makeOpenIdImplementation([
            'openid_verid_brand_uuid' => '00000000-0000-0000-0000-000000000001',
        ], openidFlow: $flow);

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
            $flow = $this->makeOpenIdFlow(['context' => $this->makeVeridContext()]);
            $implementation = $this->makeOpenIdImplementation([
                'openid_verid_brand_uuid' => '00000000-0000-0000-0000-000000000001',
            ], openidFlow: $flow);

            $this->makeOpenIdServiceWithClient($this->makeOpenIdClient())
                ->buildAuthorizationUrl($implementation, $flow);

            $this->fail('Expected OpenIdException.');
        } catch (OpenIdException $exception) {
            $this->assertInstanceOf(VerIdIntentCreationException::class, $exception->getPrevious());
        }
    }

    /**
     * @return void
     */
    public function testProviderAvailabilityDependsOnGlobalFlagAndImplementationState(): void
    {
        $implementation = $this->makeOpenIdImplementation();

        $this->assertTrue(OpenIdService::providerEnabled(OpenIdService::PROVIDER_VERID, $implementation));
        $this->assertSame([OpenIdService::PROVIDER_VERID], OpenIdService::enabledProviderKeys($implementation));

        Config::set('openid.enabled', false);

        $this->assertFalse(OpenIdService::providerEnabled(OpenIdService::PROVIDER_VERID, $implementation));
        $this->assertSame([], OpenIdService::enabledProviderKeys($implementation));

        Config::set('openid.enabled', true);
        $implementation->forceFill(['openid_enabled' => false])->save();

        $this->assertFalse(OpenIdService::providerEnabled(OpenIdService::PROVIDER_VERID, $implementation->refresh()));
        $this->assertSame([], OpenIdService::enabledProviderKeys($implementation));

        $implementation = $this->makeOpenIdImplementation(openidFlow: $this->makeOpenIdFlow([
            'key' => 'datakeeper',
            'context' => null,
        ]));

        $this->assertFalse(OpenIdService::providerEnabled(OpenIdService::PROVIDER_VERID, $implementation));

        $implementation = $this->makeOpenIdImplementation(organizationData: [
            'allow_openid' => false,
        ]);

        $this->assertFalse(OpenIdService::providerEnabled(OpenIdService::PROVIDER_VERID, $implementation));
    }

    /**
     * @throws OpenIdException
     * @return void
     */
    public function testResolveBsnFromPayloadExtractsDigitOnlyClaim(): void
    {
        $bsn = (new OpenIdService())->resolveBsnFromPayload(
            [
                'claims' => [
                    'nin' => [
                        'identifier' => '569657222',
                    ],
                ],
            ],
            $this->makeOpenIdFlow(['context' => $this->makeVeridContext()])
        );

        $this->assertSame('569657222', $bsn);
    }

    /**
     * @return void
     */
    public function testResolveBsnFromPayloadRejectsInvalidClaims(): void
    {
        $flow = $this->makeOpenIdFlow(['context' => $this->makeVeridContext()]);

        $this->assertBsnPayloadOpenIdError([], $flow);
        $this->assertBsnPayloadOpenIdError(['claims' => ['nin' => ['identifier' => 569657222]]], $flow);
        $this->assertBsnPayloadOpenIdError(['claims' => ['nin' => ['identifier' => '12345']]], $flow);
        $this->assertBsnPayloadOpenIdError(['claims' => ['nin' => ['identifier' => '569.657.222']]], $flow);
        $this->assertBsnPayloadOpenIdError(['claims' => ['nin' => ['identifier' => '569 657 222']]], $flow);
        $this->assertBsnPayloadOpenIdError(['claims' => ['nin' => ['identifier' => '569-657-222']]], $flow);
    }

    /**
     * @throws OpenIdException
     * @return void
     */
    public function testResolveCallbackSessionReturnsPendingSession(): void
    {
        $session = $this->makeOpenIdSession($this->makeOpenIdImplementation());

        $resolvedSession = (new OpenIdService())->resolveCallbackSession(
            OpenIdService::PROVIDER_VERID,
            $session->state,
        );

        $this->assertSame($session->id, $resolvedSession->id);
    }

    /**
     * @return void
     */
    public function testResolveCallbackSessionRejectsMismatchedProvider(): void
    {
        $implementation = $this->makeOpenIdImplementation();
        $session = $this->makeOpenIdSession($implementation, [
            'openid_flow' => $this->makeOpenIdFlow([
                'provider' => 'other',
                'key' => 'other_wallet',
            ]),
        ]);

        try {
            (new OpenIdService())->resolveCallbackSession(OpenIdService::PROVIDER_VERID, $session->state);
            $this->fail('Expected OpenIdException.');
        } catch (OpenIdException $exception) {
            $this->assertSame(OpenIdException::ERROR_SESSION_EXPIRED, $exception->getOpenIdError());
            $this->assertNull($exception->getOpenIdSession());
            $this->assertSame(OpenIdSession::STATE_PENDING, $session->refresh()->session_state);
        }
    }

    /**
     * @return void
     */
    public function testResolveCallbackSessionRejectsWhenSessionFlowIsDisabled(): void
    {
        $implementation = $this->makeOpenIdImplementation();
        $session = $this->makeOpenIdSession($implementation);

        $implementation->openid_flows()->detach();

        try {
            (new OpenIdService())->resolveCallbackSession(OpenIdService::PROVIDER_VERID, $session->state);
            $this->fail('Expected OpenIdException.');
        } catch (OpenIdException $exception) {
            $this->assertSame('not_enabled', $exception->getOpenIdError());
            $this->assertSame($session->id, $exception->getOpenIdSession()?->id);
        }
    }

    /**
     * @return void
     */
    public function testResolveCallbackSessionMapsDisabledGlobalConfigWithMatchingSession(): void
    {
        $session = $this->makeOpenIdSession($this->makeOpenIdImplementation());

        Config::set('openid.enabled', false);

        try {
            (new OpenIdService())->resolveCallbackSession(OpenIdService::PROVIDER_VERID, $session->state);
            $this->fail('Expected OpenIdException.');
        } catch (OpenIdException $exception) {
            $this->assertSame('not_enabled', $exception->getOpenIdError());
            $this->assertSame($session->id, $exception->getOpenIdSession()?->id);
        }
    }

    /**
     * @return void
     */
    public function testResolveCallbackSessionExpiresOldPendingSession(): void
    {
        $session = $this->makeOpenIdSession($this->makeOpenIdImplementation(), [
            'created_at' => now()->subSeconds(OpenIdSession::SESSION_EXPIRATION_TIME + 1),
        ]);

        try {
            (new OpenIdService())->resolveCallbackSession(OpenIdService::PROVIDER_VERID, $session->state);
            $this->fail('Expected OpenIdException.');
        } catch (OpenIdException $exception) {
            $this->assertSame('session_expired', $exception->getOpenIdError());
            $this->assertSame($session->id, $exception->getOpenIdSession()?->id);
            $this->assertSame(OpenIdSession::STATE_EXPIRED, $session->refresh()->session_state);
        }
    }

    /**
     * @return void
     */
    public function testResolveCallbackSessionRejectsNonPendingSession(): void
    {
        $session = $this->makeOpenIdSession($this->makeOpenIdImplementation(), [
            'session_state' => OpenIdSession::STATE_RESOLVED,
        ]);

        try {
            (new OpenIdService())->resolveCallbackSession(OpenIdService::PROVIDER_VERID, $session->state);
            $this->fail('Expected OpenIdException.');
        } catch (OpenIdException $exception) {
            $this->assertSame('session_expired', $exception->getOpenIdError());
            $this->assertSame($session->id, $exception->getOpenIdSession()?->id);
            $this->assertSame(OpenIdSession::STATE_RESOLVED, $session->refresh()->session_state);
        }
    }

    /**
     * @param array $payload
     * @param OpenIdFlow $flow
     * @return void
     */
    protected function assertBsnPayloadOpenIdError(array $payload, OpenIdFlow $flow): void
    {
        try {
            (new OpenIdService())->resolveBsnFromPayload($payload, $flow);
            $this->fail('Expected OpenIdException.');
        } catch (OpenIdException $exception) {
            $this->assertSame('missing_claims', $exception->getOpenIdError());
        }
    }

    /**
     * @param ClientInterface $client
     * @return OpenIdService
     */
    protected function makeOpenIdServiceWithClient(ClientInterface $client): OpenIdService
    {
        return new class ($client) extends OpenIdService {
            /**
             * @param ClientInterface $client
             */
            public function __construct(protected ClientInterface $client)
            {
            }

            /**
             * @param Implementation $implementation
             * @param OpenIdFlow $flow
             * @return ClientInterface
             */
            public function makeClient(Implementation $implementation, OpenIdFlow $flow): ClientInterface
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
        };
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
                    url('/api/v1/platform/openid/verid/callback'),
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
            'redirect_url' => '/api/v1/platform/openid/verid/callback',
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
