<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\BaseFormRequest;
use App\Models\Implementation;
use App\Rules\IdentityEmailExistsRule;

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
        $this->maxAttempts = env('AUTH_THROTTLE_ATTEMPTS', 10);
        $this->decayMinutes = env('AUTH_THROTTLE_DECAY', 10);
        $this->throttleWithKey('to_many_attempts', $this, 'auth');

        $emailRule = [
            'email:strict,dns',
            new IdentityEmailExistsRule()
        ];

        return array_merge(env('DISABLE_DEPRECATED_API', false) ? [
            'email' => array_merge((array) 'required', $emailRule),
        ] : [
            'email' => array_merge((array) (
                $this->has('primary_email') ? 'nullable' : 'required'
            ), $emailRule),
            'primary_email' => array_merge((array) (
                $this->has('email') ? 'nullable' : 'required'
            ), $emailRule),
        ], [
            'source' => env('DISABLE_DEPRECATED_API', false) ? [] : [
                'required',
                'in:' . Implementation::keysAvailable()->implode(',')
            ],
            'target' => [
                'nullable',
                'alpha_dash',
            ]
        ]);
    }
}
