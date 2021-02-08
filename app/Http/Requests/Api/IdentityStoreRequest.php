<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\BaseFormRequest;
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

        return [
            'email' => 'required|email:strict|unique:identity_emails,email',
            'records' => [
                'nullable',
                'array',
                new IdentityRecordsRule($this)
            ],
            'records.address' => [
                new IdentityRecordsAddressRule()
            ],
            'records.*' => 'required',
            'target' => 'nullable|alpha_dash',
        ];
    }
}
