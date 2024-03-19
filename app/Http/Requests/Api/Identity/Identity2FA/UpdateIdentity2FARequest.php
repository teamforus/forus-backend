<?php

namespace App\Http\Requests\Api\Identity\Identity2FA;

class UpdateIdentity2FARequest extends BaseIdentity2FARequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{auth_2fa_remember_ip: 'nullable|boolean'}
     */
    public function rules(): array
    {
        return [
            'auth_2fa_remember_ip' => 'nullable|boolean',
        ];
    }
}
