<?php

namespace App\Http\Requests\Api\Identity\Identity2FA;

class UpdateIdentity2FARequest extends BaseIdentity2FARequest
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
     */
    public function rules(): array
    {
        return [
            'auth_2fa_remember_ip' => 'nullable|boolean',
        ];
    }
}
