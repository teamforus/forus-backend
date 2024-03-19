<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;

class IdentityStoreRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return ((\Illuminate\Validation\Rules\Unique|mixed|string)[]|string)[]
     *
     * @throws \App\Exceptions\AuthorizationJsonException
     *
     * @psalm-return array{email: array{0: 'required'|mixed, 1: \Illuminate\Validation\Rules\Unique|mixed,...}, target: 'nullable|alpha_dash'}
     */
    public function rules(): array
    {
        $this->maxAttempts = Config::get('forus.throttles.auth.attempts');
        $this->decayMinutes = Config::get('forus.throttles.auth.decay');
        $this->throttleWithKey('to_many_attempts', $this, 'auth');

        return [
            'email' => [
                'required',
                Rule::unique('identity_emails', 'email')->whereNull('deleted_at'),
                ...$this->emailRules(),
            ],
            'target' => 'nullable|alpha_dash',
        ];
    }
}
