<?php

namespace App\Http\Requests\Api\Identity\Identity2FA;

use App\Exceptions\AuthorizationJsonException;
use App\Models\Identity2FA;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;

/**
 * @property-read Identity2FA $identity2fa
 */
class AuthenticateIdentity2FARequest extends BaseIdentity2FARequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function rules(): array
    {
        return $this->codeRules();
    }
}
