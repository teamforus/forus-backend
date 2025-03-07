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
     * Determine if the user is authorized to make this request.
     *
     * @throws AuthorizationJsonException
     * @return bool
     */
    public function authorize(): bool
    {
        $this->throttleRequest('authenticate');

        return $this->isAuthenticated() && !$this->identityProxy()->is2FAConfirmed();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     * @return array
     */
    public function rules(): array
    {
        return $this->codeRules();
    }
}
