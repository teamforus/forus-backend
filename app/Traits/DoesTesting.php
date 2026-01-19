<?php

namespace App\Traits;

use App\Models\Identity;
use App\Models\IdentityProxy;
use App\Services\Forus\TestData\TestData;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * @mixin TestCase
 */
trait DoesTesting
{
    /**
     * @param string|null $email
     * @param string|null $bsn
     * @param array $records
     * @param string|null $type
     * @param int|null $organizationId
     * @return Identity
     */
    protected function makeIdentity(
        string $email = null,
        string $bsn = null,
        array $records = [],
        string $type = null,
        int $organizationId = null
    ): Identity {
        $identity = Identity::build(email: $email, records: $records, type: $type, organizationId: $organizationId);

        if ($bsn) {
            $identity->setBsnRecord($bsn);
        }

        return $identity;
    }

    /**
     * @param Identity $identity
     * @param bool $activate
     * @param string $tokenType
     * @param string|null $ip
     * @return IdentityProxy
     */
    protected function makeIdentityProxy(
        Identity $identity,
        bool $activate = true,
        string $tokenType = 'confirmation_code',
        ?string $ip = null
    ): IdentityProxy {
        $this->assertContains($tokenType, array_keys($identity::EXPIRATION_TIMES));

        if (in_array($tokenType, ['confirmation_code', 'email_code'])) {
            if ($tokenType == 'confirmation_code') {
                $proxy = $identity->makeIdentityPoxy();

                if ($activate) {
                    Identity::exchangeEmailConfirmationToken($proxy->exchange_token, $ip);
                }

                return $proxy;
            }

            $proxy = $identity->makeAuthorizationEmailProxy();

            if ($activate) {
                Identity::activateAuthorizationEmailProxy($proxy->exchange_token, $ip);
            }

            return $proxy->refresh();
        }

        $proxy = Identity::makeProxy($tokenType);

        match($tokenType) {
            'short_token' => $identity->activateAuthorizationShortTokenProxy($proxy->exchange_token, $ip),
            'pin_code' => $identity->activateAuthorizationCodeProxy($proxy->exchange_token, $ip),
            'qr_code' => $identity->activateAuthorizationTokenProxy($proxy->exchange_token, $ip),
        };

        return $proxy->refresh();
    }

    /**
     * @param IdentityProxy|Identity|bool $authProxy
     * @param array $headers
     * @return array
     */
    protected function makeApiHeaders(
        IdentityProxy|Identity|bool $authProxy = false,
        array $headers = [],
    ): array {
        if ($authProxy instanceof Identity || $authProxy === true) {
            $authProxy = $this->makeIdentityProxy(
                $authProxy instanceof Identity ? $authProxy : $this->makeIdentity()
            );
        }

        return array_merge([
            'Authorization' => $authProxy ? "Bearer $authProxy->access_token" : null,
        ], $headers);
    }

    /**
     * @param callable $callable
     * @param string $message
     * @return mixed
     */
    protected function assertNoException(
        callable $callable,
        string $message = 'No exception assertion failed',
    ): mixed {
        try {
            return $callable();
        } catch (Throwable) {
            self::fail($message);
        }
    }

    /**
     * @return string
     */
    protected function makeIban(): string
    {
        return $this->faker()->iban('NL');
    }

    /**
     * @throws Throwable
     * @return int
     */
    protected function randomFakeBsn(): int
    {
        return TestData::randomFakeBsn();
    }

    /**
     * @return string
     */
    protected function makeIbanName(): string
    {
        return preg_replace(
            '/[^a-zA-Z .]+/',
            '',
            $this->faker()->firstName . ' ' . $this->faker()->lastName
        );
    }
}
