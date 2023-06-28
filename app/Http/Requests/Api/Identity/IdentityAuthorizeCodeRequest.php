<?php

namespace App\Http\Requests\Api\Identity;

use App\Http\Requests\BaseFormRequest;

class IdentityAuthorizeCodeRequest extends BaseFormRequest
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
     * @return array
     */
    public function rules(): array
    {
        return [
            'auth_code' => 'required|string',
            'share_2fa' => 'required|boolean',
        ];
    }
}
