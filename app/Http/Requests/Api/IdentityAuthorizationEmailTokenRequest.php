<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\BaseFormRequest;
use App\Rules\IdentityEmailExistsRule;
use App\Models\Implementation;
use Illuminate\Support\Facades\Config;

/**
 * Class IdentityAuthorizationEmailTokenRequest
 * @package App\Http\Requests\Api
 */
class IdentityAuthorizationEmailTokenRequest extends BaseFormRequest
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
     * @return array
     * @throws \App\Exceptions\AuthorizationJsonException
     */
    public function rules(): array
    {
        $this->maxAttempts = Config::get('forus.throttles.auth.attempts');
        $this->decayMinutes = Config::get('forus.throttles.auth.decay');
        $this->throttleWithKey('to_many_attempts', $this, 'auth');

        return [
            'email' => [
                'required',
                new IdentityEmailExistsRule(),
                ...$this->emailRules(),
            ],
            'source' => 'required|in:' . Implementation::keysAvailable()->implode(','),
            'target' => 'nullable|alpha_dash'
        ];
    }
}
