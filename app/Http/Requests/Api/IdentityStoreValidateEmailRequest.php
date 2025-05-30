<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Facades\Config;

class IdentityStoreValidateEmailRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @throws \App\Exceptions\AuthorizationJsonException
     * @return array
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
