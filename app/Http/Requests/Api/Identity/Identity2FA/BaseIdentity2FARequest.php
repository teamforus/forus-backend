<?php

namespace App\Http\Requests\Api\Identity\Identity2FA;

use App\Http\Requests\BaseFormRequest;
use App\Models\Identity2FA;
use App\Models\Identity2FACode;
use Illuminate\Support\Facades\Config;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FA\Google2FA;

/**
 * @property-read Identity2FA $identity2fa
 */
abstract class BaseIdentity2FARequest extends BaseFormRequest
{
    /**
     * @param string $key
     * @return void
     * @throws \App\Exceptions\AuthorizationJsonException
     */
    protected function throttleRequest(string $key): void
    {
        $this->maxAttempts = Config::get('forus.auth_2fa.throttle_attempts');
        $this->decayMinutes = Config::get('forus.auth_2fa.throttle_decay');
        $this->throttleWithKey('to_many_attempts', $this, 'auth_2fa', $key);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function codeRules(): array
    {
        $code = $this->string('code', '')->toString();

        if ($this->identity2fa->auth_2fa_provider->isTypePhone()) {
            return $this->rulesPhone($code, $this->identity2fa);
        }

        if ($this->identity2fa->auth_2fa_provider->isTypeAuthenticator()) {
            return $this->rulesAuthenticator($code, $this->identity2fa);
        }

        return [
            'code' => 'required|string|in:',
        ];
    }

    /**
     * @return string[]
     */
    public function rulesPhone(string $code, Identity2FA $identity2FA): array
    {
        $isValid = $identity2FA
            ->identity_2fa_codes()
            ->where('expire_at', '>', now())
            ->where('state', Identity2FACode::STATE_ACTIVE)
            ->where('code', $code)
            ->exists();

        return [
            'code' => 'required|string|size:6' . ($isValid ? '' : '|in:'),
        ];
    }

    /**
     * @return string[]
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function rulesAuthenticator(string $code, Identity2FA $identity2FA): array
    {
        $google2FA = new Google2FA();
        $isValid = $google2FA->verifyKey($identity2FA->secret, $code);

        return [
            'code' => 'required|string|size:6' . ($isValid ? '' : '|in:'),
        ];
    }
}
