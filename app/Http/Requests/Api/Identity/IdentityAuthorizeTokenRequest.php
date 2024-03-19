<?php

namespace App\Http\Requests\Api\Identity;

use App\Http\Requests\BaseFormRequest;

class IdentityAuthorizeTokenRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{auth_token: 'required'}
     */
    public function rules(): array
    {
        return [
            'auth_token' => 'required'
        ];
    }
}
