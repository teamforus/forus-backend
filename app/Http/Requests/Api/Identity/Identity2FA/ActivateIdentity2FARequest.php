<?php

namespace App\Http\Requests\Api\Identity\Identity2FA;

use App\Exceptions\AuthorizationJsonException;
use Illuminate\Validation\Rule;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;

class ActivateIdentity2FARequest extends BaseIdentity2FARequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @throws AuthorizationJsonException
     * @return bool
     */
    public function authorize(): bool
    {
        $this->throttleRequest('active');

        return $this->isAuthenticated() && $this->identity2fa->identity_address == $this->auth_address();
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
        $type = $this->identity2fa->auth_2fa_provider->type;

        return array_merge(parent::codeRules(), [
            'key' => [
                'required',
                Rule::exists('auth_2fa_providers', 'key')->where('type', $type),
            ],
        ]);
    }
}
