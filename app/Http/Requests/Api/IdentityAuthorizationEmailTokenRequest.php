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
     * Get the validation rules that apply to the request.
     *
     * @return ((IdentityEmailExistsRule|mixed|string)[]|string)[]
     *
     * @throws \App\Exceptions\AuthorizationJsonException
     *
     * @psalm-return array{email: array{0: 'required'|mixed, 1: IdentityEmailExistsRule|mixed,...}, source: string, target: 'nullable|alpha_dash'}
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
