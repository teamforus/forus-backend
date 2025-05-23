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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\MakesTest2FA;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class Identity2FATest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;
    use MakesTestIdentities;
    use MakesTestOrganizations;
    use MakesTestFunds;
    use MakesTest2FA;

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
     * @return void
     */
    public function test2FAThrottle(): void
    {
        Cache::clear();

        $identityProxy = $this->makeIdentityProxy($this->makeIdentity($this->makeUniqueEmail()));
        $identityProxy = $this->makeIdentityProxy($identityProxy->identity);
        $maxAttempts = Config::get('forus.auth_2fa.throttle_attempts') + 1;

        foreach (range(1, $maxAttempts) as $item) {
            $this->setup2FAProvider(
                $identityProxy,
                'authenticator',
                [],
                $item === $maxAttempts ? 429 : null
            );
        }

        Cache::clear();
    }

    /**
     * @return void
     */
    public function testSamePhone2FA(): void
    {
        $identityProxy = $this->makeIdentityProxy($this->makeIdentity($this->makeUniqueEmail()));
        $identity2FA = $this->setup2FAProvider($identityProxy, 'phone');
        $this->assertNotNull($identity2FA);

        $identityProxy2 = $this->makeIdentityProxy($this->makeIdentity($this->makeUniqueEmail()));
        $identity2FA2 = $this->setup2FAProvider($identityProxy2, 'phone');
        $this->assertNotNull($identity2FA);

        $this->activate2FAProvider($identityProxy, $identity2FA);
        $this->assertIdentity2FAHasActiveProviders($identityProxy, true, ['phone']);

        $this->activate2FAProvider($identityProxy2, $identity2FA2, false);
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
        $this->assertNotNull($identity2FA);
        $this->assertResendCode($identityProxy, $identity2FA);
        $this->activate2FAProvider($identityProxy, $identity2FA);
        $this->assertIdentity2FAHasActiveProviders($identityProxy, true, ['phone']);

        // setup authenticator 2fa provider and assert the providers is now listed
        $identity2FA = $this->setup2FAProvider($identityProxy, 'authenticator');
        $this->assertNotNull($identity2FA);
        $this->activate2FAProvider($identityProxy, $identity2FA);
        $this->assertIdentity2FAHasActiveProviders($identityProxy, true, ['phone', 'authenticator']);

        // assert identity can't set same provider
        $this->setup2FAProvider($identityProxy, 'authenticator', [], 403);

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

        $identityProxyForInvalidPhone = $this->makeIdentityProxy($this->makeIdentity($this->makeUniqueEmail()));
        $identity2FAInvalid = $this->setup2FAProvider($identityProxyForInvalidPhone, 'phone', ['phone']);
        $this->assertNull($identity2FAInvalid);

        $this->deactivate2FAProvider($identityProxy, $identity2FA);

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
        $this->assertNotNull($identity2FA);
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
            'error' => '2fa',
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
            ],
        ]);

        if ($required) {
            $this->assertTrue($response->json('data.required'));
        } else {
            $this->assertFalse($response->json('data.required'));
        }

        $providers = $response->json('data.active_providers');
        $this->assertIsArray($providers);

        $active_keys = array_map(fn ($provider) => Arr::get($provider, 'provider_type.type'), $providers);
        array_walk($active_provider_types, fn ($key) => $this->assertContains($key, $active_keys));
    }

    /**
     * @return Identity
     */
    protected function makeIdentityWithForce2fa(): Identity
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity, [
            'auth_2fa_policy' => Organization::AUTH_2FA_POLICY_REQUIRED,
        ]);

        $organization->addEmployee($identity, Role::pluck('id')->toArray());

        $this->makeTestFund($organization);
        $this->makeTestFund($organization);
        $this->makeTestFund($organization);

        return $identity;
    }

    /**
     * @throws Throwable
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
        array $apiHeaders,
    ): void {
        $response = $this->postJson("/api/v1/identity/2fa/$identity2FA->uuid/activate", [
            'key' => $identity2FA->auth_2fa_provider->key,
            'code' => $deactivatedCode,
        ], $apiHeaders);

        $response->assertJsonValidationErrorFor('code');
    }

    /**
     * @param IdentityProxy $identityProxy
     * @param Identity2FA $identity2FA
     * @return Identity2FA
     */
    protected function assertResendCode(
        IdentityProxy $identityProxy,
        Identity2FA $identity2FA,
    ): Identity2FA {
        // check resend and throttle
        $maxAttempts = Config::get('forus.auth_2fa.resend_throttle_attempts') + 1;

        foreach (range(1, $maxAttempts) as $item) {
            $this->resendCode($identityProxy, $identity2FA, $item === $maxAttempts);
        }

        return $identity2FA;
    }

    /**
     * @param IdentityProxy $identityProxy
     * @param Identity2FA $identity2FA
     * @param bool $assertThrottle
     * @return Identity2FA
     */
    protected function resendCode(
        IdentityProxy $identityProxy,
        Identity2FA $identity2FA,
        bool $assertThrottle = false,
    ): Identity2FA {
        $headers = $this->makeApiHeaders($identityProxy);
        $response = $this->postJson("/api/v1/identity/2fa/$identity2FA->uuid/resend", [], $headers);

        if ($assertThrottle) {
            $response->assertTooManyRequests();
        } else {
            $response->assertStatus(200);
            $response->assertJsonPath('code_sent', true);
        }

        return $identity2FA->refresh();
    }

    /**
     * @param IdentityProxy $identityProxy
     * @param string $type
     * @param bool $assertThrottle
     * @return void
     */
    private function authenticateBy2FA(
        IdentityProxy $identityProxy,
        string $type,
        bool $assertThrottle = false
    ): void {
        /** @var Identity2FA $identity2FA */
        $identity2FA = $identityProxy->identity
            ->identity_2fa_active()
            ->whereRelation('auth_2fa_provider', 'type', $type)
            ->first();

        $this->assertNotNull($identity2FA);

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

            return $this->postJson("/api/v1/identity/2fa/$identity2FA->uuid/authenticate", [
                'code' => $code,
            ], $this->makeApiHeaders($identityProxy));
        });

        $assertThrottle ? $response->assertTooManyRequests() : $response->assertStatus(200);
    }
}
