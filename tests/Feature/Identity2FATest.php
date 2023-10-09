<?php

namespace Tests\Feature;

use App\Helpers\Arr;
use App\Models\Identity;
use App\Models\Identity2FA;
use App\Models\Identity2FACode;
use App\Models\IdentityProxy;
use App\Models\Organization;
use App\Models\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\MakesTestIdentities;

class Identity2FATest extends TestCase
{
    use DatabaseTransactions, WithFaker, MakesTestIdentities;

    /**
     * @return void
     */
    public function test2FASetupRequiredAuthenticator(): void
    {
        $this->doTest2FASetupRequired('authenticator');
    }

    /**
     * @return void
     */
    public function test2FASetupRequiredPhone(): void
    {
        $this->doTest2FASetupRequired('phone');
    }

    /**
     * @return void
     */
    public function testAuthenticator2FASignIn(): void
    {
        $this->doTest2FASignIn('authenticator');
    }

    /**
     * @return void
     */
    public function testPhone2FASignIn(): void
    {
        $this->doTest2FASignIn('phone');
    }

    /**
     * @return void
     */
    public function testMultiple2FAProviders(): void
    {
        $this->doTestMultiple2FA();
    }

    /**
     * @return IdentityProxy
     */
    protected function doTestMultiple2FA(): IdentityProxy
    {
        $identityProxy = $this->makeIdentityProxy($this->makeIdentity($this->makeUniqueEmail()));

        // assert identity has no 2fa providers configured
        $this->assertIdentity2FAHasActiveProviders($identityProxy, false, []);

        // setup phone 2fa provider and assert the providers is now listed
        $identityProxy = $this->makeIdentityProxy($identityProxy->identity);
        $identity2FA = $this->setup2FAProvider($identityProxy, 'phone');
        $this->activate2FAProvider($identityProxy, $identity2FA);
        $this->assertIdentity2FAHasActiveProviders($identityProxy, true, ['phone']);

        // setup authenticator 2fa provider and assert the providers is now listed
        $identity2FA = $this->setup2FAProvider($identityProxy, 'authenticator');
        $this->activate2FAProvider($identityProxy, $identity2FA);
        $this->assertIdentity2FAHasActiveProviders($identityProxy, true, ['phone', 'authenticator']);

        // make new proxy and assert that 2fa is required and both providers are available
        // then sign in by phone provider
        $identityProxy = $this->makeIdentityProxy($identityProxy->identity);
        $this->assertIdentity2FAState($identityProxy, ['phone', 'authenticator']);
        $this->authenticateBy2FA($identityProxy, 'phone');
        $this->assertIdentity2FAHasActiveProviders($identityProxy, true, ['phone', 'authenticator']);

        // make new proxy and assert that 2fa is required and both providers are available
        // then sign in by authenticator provider
        $identityProxy = $this->makeIdentityProxy($identityProxy->identity);
        $this->assertIdentity2FAState($identityProxy, ['phone', 'authenticator']);
        $this->authenticateBy2FA($identityProxy, 'authenticator');
        $this->assertIdentity2FAHasActiveProviders($identityProxy, true, ['phone', 'authenticator']);

        return $identityProxy;
    }

    /**
     * @param string $type
     * @return IdentityProxy
     */
    protected function doTest2FASignIn(string $type): IdentityProxy
    {
        $identity = $this->doTest2FASetupRequired($type)->identity;
        $identityProxy = $this->makeIdentityProxy($identity);

        $this->assertIdentity2FAState($identityProxy, [$type]);
        $this->authenticateBy2FA($identityProxy, $type);
        $this->assertIdentity2FAHasActiveProviders($identityProxy, true, [$type]);

        return $identityProxy;
    }

    /**
     * @param string $type
     * @return IdentityProxy
     */
    protected function doTest2FASetupRequired(string $type): IdentityProxy
    {
        $identityProxy = $this->makeIdentityProxy($this->makeIdentityWithForce2fa());
        $apiHeaders = $this->makeApiHeaders($identityProxy);

        // assert 2fa is required
        $this->assertIdentity2FAState($identityProxy);

        // assert 2fa request is created
        $identity2FA = $this->setup2FAProvider($identityProxy, $type);
        $this->activate2FAProvider($identityProxy, $identity2FA);

        // assert 2fa is active
        $this->getJson('/api/v1/identity', $apiHeaders)->assertStatus(200);

        return $identityProxy;
    }

    /**
     * @param IdentityProxy $identityProxy
     * @param array $active_provider_types
     * @return void
     */
    protected function assertIdentity2FAState(
        IdentityProxy $identityProxy,
        array $active_provider_types = [],
    ): void {
        $apiHeaders = $this->makeApiHeaders($identityProxy);

        $response = $this->getJson('/api/v1/identity', $apiHeaders);
        $response->assertStatus(401);
        $response->assertExactJson([
            "error" => "2fa",
        ]);

        $this->assertIdentity2FAHasActiveProviders($identityProxy, true, $active_provider_types);
    }

    /**
     * @param IdentityProxy $identityProxy
     * @param bool $required
     * @param array $active_provider_types
     * @return void
     */
    protected function assertIdentity2FAHasActiveProviders(
        IdentityProxy $identityProxy,
        bool $required,
        array $active_provider_types,
    ): void {
        $apiHeaders = $this->makeApiHeaders($identityProxy);
        $response = $this->getJson('/api/v1/identity/2fa', $apiHeaders);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'required',
                'providers',
                'provider_types',
                'active_providers',
            ]
        ]);

        if ($required) {
            $this->assertTrue($response->json('data.required'));
        } else {
            $this->assertFalse($response->json('data.required'));
        }

        $providers = $response->json('data.active_providers');
        $this->assertIsArray($providers);

        $active_keys = array_map(fn($provider) => Arr::get($provider, 'provider_type.type'), $providers);
        array_walk($active_provider_types, fn ($key) => $this->assertTrue(in_array($key, $active_keys)));
    }

    /**
     * @param IdentityProxy $identityProxy
     * @param string $type
     * @return Identity2FA
     */
    protected function setup2FAProvider(IdentityProxy $identityProxy, string $type): Identity2FA
    {
        $apiHeaders = $this->makeApiHeaders($identityProxy);
        $response = $this->postJson('/api/v1/identity/2fa', match ($type) {
            'phone' => [
                'type' => 'phone',
                'phone' => '31123456789',
            ],
            'authenticator' => [
                'type' => 'authenticator',
            ],
            default => [],
        }, $apiHeaders);

        $response->assertStatus(201);
        $response->assertJsonPath('data.state', 'pending');

        $uuid = $response->json('data.uuid');
        $phone = $response->json('data.phone');
        $secret = $response->json('data.secret');
        $secretUrl = $response->json('data.secret_url');
        $identity2FA = Identity2FA::find($uuid);

        switch ($type) {
            case 'phone': {
                $this->assertNotEmpty($uuid);
                $this->assertEmpty($secret);
                $this->assertNotEmpty($phone);
            } break;
            case 'authenticator': {
                $this->assertNotEmpty($uuid);
                $this->assertNotEmpty($secret);
                $this->assertNotEmpty($secretUrl);
            } break;
        }

        return $identity2FA;
    }

    /**
     * @param IdentityProxy $identityProxy
     * @param Identity2FA $identity2FA
     * @return Identity2FA
     */
    protected function activate2FAProvider(
        IdentityProxy $identityProxy,
        Identity2FA $identity2FA,
    ): Identity2FA {
        // assert 2fa confirmation/activation works
        /** @var TestResponse $response */
        $response = $this->assertNoException(function () use ($identityProxy, $identity2FA) {
            if ($identity2FA->isTypeAuthenticator()) {
                $code = $identity2FA->makeAuthenticatorCode();
            } else {
                $deactivatedCode = $this->sendCodeToPhone($identity2FA);
                sleep(1);

                $code = $this->sendCodeToPhone($identity2FA);
                $this->assertInvalidCode($identity2FA, $deactivatedCode, $this->makeApiHeaders($identityProxy));
            }

            return $this->postJson("/api/v1/identity/2fa/$identity2FA->uuid/activate", [
                'key' => $identity2FA->auth_2fa_provider->key,
                'code' => "$code",
            ], $this->makeApiHeaders($identityProxy));
        });

        $response->assertStatus(200);
        $response->assertJsonPath('data.state', 'active');

        return $identity2FA->refresh();
    }

    /**
     * @return Identity
     */
    protected function makeIdentityWithForce2fa(): Identity
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = Organization::whereHas('funds')->first();

        $organization->updateModel([
            'auth_2fa_policy' => Organization::AUTH_2FA_POLICY_REQUIRED,
        ])->addEmployee($identity, Role::pluck('id')->toArray());

        return $identity;
    }

    /**
     * @param IdentityProxy $identityProxy
     * @param string $type
     * @return void
     */
    private function authenticateBy2FA(IdentityProxy $identityProxy, string $type): void
    {
        /** @var Identity2FA $identity2FA */
        $identity2FA = $identityProxy->identity
            ->identity_2fa_active()
            ->whereRelation('auth_2fa_provider', 'type', $type)
            ->first();

        $this->assertNotNull($identity2FA);

        $response = $this->assertNoException(function () use ($identityProxy, $identity2FA) {
            if ($identity2FA->isTypeAuthenticator()) {
                $code = $identity2FA->makeAuthenticatorCode();
            } else {
                $deactivatedCode = $this->sendCodeToPhone($identity2FA);
                sleep(1);

                $code = $this->sendCodeToPhone($identity2FA);
                $this->assertInvalidCode($identity2FA, $deactivatedCode, $this->makeApiHeaders($identityProxy));
            }

            return $this->postJson("/api/v1/identity/2fa/$identity2FA->uuid/authenticate", [
                'code' => "$code",
            ], $this->makeApiHeaders($identityProxy));
        });

        $response->assertStatus(200);
    }

    /**
     * @throws \Throwable
     */
    protected function sendCodeToPhone(Identity2FA $identity2FA): string
    {
        $now = now();
        $identity2FA->sendCode();

        /** @var Identity2FACode $code */
        $code = $identity2FA->identity_2fa_codes()
            ->where('created_at', '>=', $now)
            ->first();

        $this->assertNotNull($code);

        return $code->code;
    }

    /**
     * @param Identity2FA $identity2FA
     * @param string $deactivatedCode
     * @param array $apiHeaders
     * @return void
     */
    protected function assertInvalidCode(
        Identity2FA $identity2FA,
        string $deactivatedCode,
        array $apiHeaders
    ): void {
        $response = $this->postJson("/api/v1/identity/2fa/$identity2FA->uuid/activate", [
            'key' => $identity2FA->auth_2fa_provider->key,
            'code' => "$deactivatedCode",
        ], $apiHeaders);

        $response->assertJsonValidationErrorFor('code');
    }
}
