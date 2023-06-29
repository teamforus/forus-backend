<?php

namespace App\Services\Forus\Auth2FAService;

use App\Services\Forus\Auth2FAService\Data\Auth2FASecret;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;

class Auth2FAService
{
    /**
     * @param string $company
     * @param string $holder
     * @return Auth2FASecret
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function make2FASecret(string $company, string $holder): Auth2FASecret
    {
        return new Auth2FASecret($company, $holder);
    }
}