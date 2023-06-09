<?php

namespace App\Services\Forus\Auth2FAService\Data;

use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FA\Google2FA;

class Auth2FASecret
{
    protected string $secretKey;

    protected string $secretUrl;

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function __construct(string $company, string $email)
    {
        $google2fa = new Google2FA();
        $this->secretKey = $google2fa->generateSecretKey();
        $this->secretUrl = $google2fa->getQRCodeUrl($company, $email, $this->secretKey);
    }

    /**
     * @return string
     */
    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    /**
     * @return string
     */
    public function getSecretUrl(): string
    {
        return $this->secretUrl;
    }
}