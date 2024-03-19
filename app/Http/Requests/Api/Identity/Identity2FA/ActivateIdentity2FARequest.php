<?php

namespace App\Http\Requests\Api\Identity\Identity2FA;

use App\Exceptions\AuthorizationJsonException;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;

class ActivateIdentity2FARequest extends BaseIdentity2FARequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return ((\Illuminate\Validation\Rules\Exists|string)[]|mixed)[]
     *
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     *
     * @psalm-return array{key: list{'required', \Illuminate\Validation\Rules\Exists},...}
     */
    public function rules(): array
    {
        $type = $this->identity2fa->auth_2fa_provider->type;

        return array_merge(parent::codeRules(), [
            'key' => [
                'required',
                Rule::exists('auth_2fa_providers', 'key')->where('type', $type),
            ]
        ]);
    }
}
