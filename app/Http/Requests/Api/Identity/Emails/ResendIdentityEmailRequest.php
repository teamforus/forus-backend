<?php

namespace App\Http\Requests\Api\Identity\Emails;

use App\Models\IdentityEmail;

/**
 * @property IdentityEmail $identity_email
 */
class ResendIdentityEmailRequest extends BaseIdentityEmailRequest
{
    public function authorize(): bool
    {
        return $this->isAuthenticated() &&
            $this->identity_email->identity_address === $this->auth_address();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     * @throws \App\Exceptions\AuthorizationJsonException
     */
    public function rules(): array
    {
        $this->throttleWithKey('to_many_attempts', $this, 'email');

        return [];
    }
}
