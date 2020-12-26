<?php

namespace App\Http\Requests\Api\Identity\Emails;

use App\Rules\IdentityEmailUniqueRule;

/**
 * Class StoreIdentityEmailRequest
 * @package App\Http\Requests\Api\Identity\Emails
 */
class StoreIdentityEmailRequest extends BaseIdentityEmailRequest
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
        $this->throttleWithKey('to_many_attempts', $this, 'email');

        return [
            'email' => [
                'required', new IdentityEmailUniqueRule()
            ]
        ];
    }
}
