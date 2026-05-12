<?php

namespace Tests\Unit;

use App\Models\Implementation;
use App\Services\OpenIdService\Models\OpenIdSession;
use App\Services\OpenIdService\OpenIdException;
use App\Services\OpenIdService\OpenIdService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
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
    public function testProviderConfigUsesImplementationContextOnly(): void
    {
        Config::set('openid.providers', [
            OpenIdService::PROVIDER_VERID => [
                'issuer' => 'https://wrong.example',
                'client_id' => 'wrong-client',
                'client_secret' => 'wrong-secret',
                'redirect_url' => '/wrong/callback',
                'scopes' => ['wrong'],
                'bsn_claim' => 'wrong.claim',
                'bsn_claim_source' => 'user_info',
                'auth_params' => ['prompt' => 'wrong'],
                'code_challenge_method' => 'plain',
                'id_token_signed_response_alg' => 'RS256',
                'token_endpoint_auth_method' => 'client_secret_post',
            ],
        ]);

        $config = (new OpenIdService())->getProviderConfig(
            OpenIdService::PROVIDER_VERID,
            new Implementation([
                'openid_verid_context' => $this->makeVeridContext(),
            ])
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
            OpenIdService::PROVIDER_VERID,
            new Implementation([
                'openid_verid_context' => $this->makeVeridContext([
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
            $this->makeVeridContext(['issuer' => ['https://issuer.example']])
        ));
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
        $implementation->forceFill(['openid_verid_enabled' => false])->save();

        $this->assertFalse(OpenIdService::providerEnabled(OpenIdService::PROVIDER_VERID, $implementation->refresh()));
        $this->assertSame([], OpenIdService::enabledProviderKeys($implementation));

        $implementation = $this->makeOpenIdImplementation([
            'openid_verid_context' => null,
        ]);

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
            OpenIdService::PROVIDER_VERID,
            [
                'claims' => [
                    'nin' => [
                        'identifier' => '569657222',
                    ],
                ],
            ],
            new Implementation([
                'openid_verid_context' => $this->makeVeridContext(),
            ])
        );

        $this->assertSame('569657222', $bsn);
    }

    /**
     * @throws OpenIdException
     * @return void
     */
    public function testResolveBsnFromPayloadCanUseUserInfoSource(): void
    {
        $bsn = (new OpenIdService())->resolveBsnFromPayload(
            OpenIdService::PROVIDER_VERID,
            [
                'claims' => [],
                'user_info' => [
                    'profile' => [
                        'bsn' => '834884148',
                    ],
                ],
            ],
            new Implementation([
                'openid_verid_context' => $this->makeVeridContext([
                    'bsn_claim' => 'profile.bsn',
                    'bsn_claim_source' => 'user_info',
                ]),
            ])
        );

        $this->assertSame('834884148', $bsn);
    }

    /**
     * @return void
     */
    public function testResolveBsnFromPayloadRejectsInvalidClaims(): void
    {
        $implementation = new Implementation([
            'openid_verid_context' => $this->makeVeridContext(),
        ]);

        $this->assertBsnPayloadOpenIdError([], $implementation);
        $this->assertBsnPayloadOpenIdError(['claims' => ['nin' => ['identifier' => 569657222]]], $implementation);
        $this->assertBsnPayloadOpenIdError(['claims' => ['nin' => ['identifier' => '12345']]], $implementation);
        $this->assertBsnPayloadOpenIdError(['claims' => ['nin' => ['identifier' => '569.657.222']]], $implementation);
        $this->assertBsnPayloadOpenIdError(['claims' => ['nin' => ['identifier' => '569 657 222']]], $implementation);
        $this->assertBsnPayloadOpenIdError(['claims' => ['nin' => ['identifier' => '569-657-222']]], $implementation);

        $this->assertBsnPayloadOpenIdError([
            'claims' => [
                'nin' => [
                    'identifier' => '569657222',
                ],
            ],
        ], new Implementation([
            'openid_verid_context' => $this->makeVeridContext([
                'bsn_claim_source' => 'unsupported',
            ]),
        ]));
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
     * @param Implementation $implementation
     * @return void
     */
    protected function assertBsnPayloadOpenIdError(array $payload, Implementation $implementation): void
    {
        try {
            (new OpenIdService())->resolveBsnFromPayload(OpenIdService::PROVIDER_VERID, $payload, $implementation);
            $this->fail('Expected OpenIdException.');
        } catch (OpenIdException $exception) {
            $this->assertSame('missing_claims', $exception->getOpenIdError());
        }
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
