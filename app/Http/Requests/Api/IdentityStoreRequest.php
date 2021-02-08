<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\BaseFormRequest;
use App\Rules\IdentityPinCodeRule;
use App\Rules\IdentityRecordsAddressRule;
use App\Rules\IdentityRecordsRule;

class IdentityStoreRequest extends BaseFormRequest
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
            'unique:identity_emails,email',
        ];

        return [
            'pin_code' => [
                'nullable',
                new IdentityPinCodeRule()
            ],
            'email' => array_merge((array) (
                $this->has('records.primary_email') ? 'nullable' : 'required'
            ), $emailRule),
            'records' => [
                !env('DISABLE_DEPRECATED_API', false) && !$this->has('email') ? 'required' : 'nullable',
                'array',
                new IdentityRecordsRule($this)
            ],
            'records.primary_email' => array_merge((array) (
                $this->has('email') ? 'nullable' : 'required'
            ), $emailRule),
            'records.address' => [
                new IdentityRecordsAddressRule()
            ],
            'records.*' => [
                'required'
            ],
            'target' => [
                'nullable',
                'alpha_dash',
            ]
        ];
    }
}
