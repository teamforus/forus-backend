<?php

namespace App\Traits;

use App\Models\Identity;
use App\Models\IdentityProxy;
use PHPUnit\Framework\TestCase;

/**
 * @mixin TestCase
 */
trait DoesTesting
{
    /**
     * @param string|null $email
     * @param array $records
     * @return Identity
     */
    protected function makeIdentity(string $email = null, array $records = []): Identity
    {
        return Identity::make($email, $records);
    }

    /**
     * @param Identity $identity
     * @param bool $activate
     * @param string $tokenType
     * @return IdentityProxy
     */
    protected function makeIdentityProxy(
        Identity $identity,
        bool $activate = true,
        string $tokenType = 'confirmation_code',
    ): IdentityProxy {
        $this->assertContains($tokenType, array_keys($identity::expirationTimes));

        if (in_array($tokenType, ['confirmation_code', 'email_code'])) {
            if ($tokenType == 'confirmation_code') {
                $proxy = $identity->makeIdentityPoxy();

                if ($activate) {
                    Identity::exchangeEmailConfirmationToken($proxy->exchange_token);
                }

                return $proxy;
            }

            $proxy = $identity->makeAuthorizationEmailProxy();

            if ($activate) {
                Identity::activateAuthorizationEmailProxy($proxy->exchange_token);
            }

            return $proxy->refresh();
        }

        $proxy = Identity::makeProxy($tokenType);

        match($tokenType) {
            'short_token' => $identity->activateAuthorizationShortTokenProxy($proxy->exchange_token),
            'pin_code' => $identity->activateAuthorizationCodeProxy($proxy->exchange_token),
            'qr_code' => $identity->activateAuthorizationTokenProxy($proxy->exchange_token),
        };

        return $proxy->refresh();
    }

    /**
     * @param IdentityProxy|bool|null $authProxy
     * @param array $headers
     * @return array
     */
    protected function makeApiHeaders(IdentityProxy|bool $authProxy = false, array $headers = []): array
    {
        if ($authProxy === true) {
            $authProxy = $this->makeIdentityProxy($this->makeIdentity());
        }

        return array_merge([
            'Authorization' => $authProxy ? "Bearer $authProxy->access_token" : null,
        ], $headers);
    }
}