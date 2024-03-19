<?php

namespace App\Http\Requests\Api\Identity;

use App\Http\Requests\BaseFormRequest;

class IdentityAuthorizeCodeRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{auth_code: 'required|string'}
     */
    public function rules(): array
    {
        return [
            'auth_code' => 'required|string',
        ];
    }
}
