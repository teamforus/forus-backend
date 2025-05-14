<?php

namespace Tests\Traits;

use App\Models\Identity2FA;
use App\Models\IdentityProxy;
use Illuminate\Testing\TestResponse;

trait MakesTest2FA
{
    /**
     * @param IdentityProxy $identityProxy
     * @param string $type
     * @param array $errors
     * @param int|null $assertStatus
     * @return Identity2FA|null
     */
    protected function setup2FAProvider(
        IdentityProxy $identityProxy,
        string $type,
        array $errors = [],
        ?int $assertStatus = null,
    ): ?Identity2FA {
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

        if (count($errors)) {
            $response->assertJsonValidationErrors($errors);

            return null;
        }

        if ($assertStatus) {
            $response->assertStatus($assertStatus);

            return null;
        }

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
     * @param bool $assertSuccess
     * @return Identity2FA
     */
    protected function activate2FAProvider(
        IdentityProxy $identityProxy,
        Identity2FA $identity2FA,
        bool $assertSuccess = true
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
                'code' => $code,
            ], $this->makeApiHeaders($identityProxy));
        });

        if ($assertSuccess) {
            $response->assertStatus(200);
            $response->assertJsonPath('data.state', 'active');
        } else {
            $response->assertForbidden();
        }

        return $identity2FA->refresh();
    }

    /**
     * @param IdentityProxy $identityProxy
     * @param Identity2FA $identity2FA
     * @return Identity2FA
     */
    protected function deactivate2FAProvider(
        IdentityProxy $identityProxy,
        Identity2FA $identity2FA,
    ): Identity2FA {
        /** @var TestResponse $response */
        $response = $this->assertNoException(function () use ($identityProxy, $identity2FA) {
            $code = $identity2FA->isTypeAuthenticator()
                ? $identity2FA->makeAuthenticatorCode()
                : $this->sendCodeToPhone($identity2FA);

            return $this->postJson("/api/v1/identity/2fa/$identity2FA->uuid/deactivate", [
                'key' => $identity2FA->auth_2fa_provider->key,
                'code' => $code,
            ], $this->makeApiHeaders($identityProxy));
        });

        $response->assertStatus(200);

        return $identity2FA->refresh();
    }
}
