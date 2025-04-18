<?php

namespace App\Http\Requests\Api\Identity\Emails;

use App\Rules\IdentityEmailMaxRule;
use App\Rules\IdentityEmailUniqueRule;

class StoreIdentityEmailRequest extends BaseIdentityEmailRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->isAuthenticated();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @throws \App\Exceptions\AuthorizationJsonException
     * @return array
     */
    public function rules(): array
    {
        $this->throttleWithKey('to_many_attempts', $this, 'email');

        return [
            'target' => 'nullable|alpha_dash',
            'email' => [
                'required',
                new IdentityEmailUniqueRule(),
                new IdentityEmailMaxRule($this->auth_address()),
                ...$this->emailRules(),
            ],
        ];
    }
}
