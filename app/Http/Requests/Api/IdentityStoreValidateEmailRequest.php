<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Facades\Config;

class IdentityStoreValidateEmailRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[][]
     *
     * @throws \App\Exceptions\AuthorizationJsonException
     *
     * @psalm-return array{email: list{'required'}}
     */
    public function rules(): array
    {
        $this->maxAttempts = Config::get('forus.throttles.auth.attempts');
        $this->decayMinutes = Config::get('forus.throttles.auth.decay');
        $this->throttleWithKey('to_many_attempts', $this, 'auth');

        return [
            'email' => [
                'required',
            ],
        ];
    }
}
