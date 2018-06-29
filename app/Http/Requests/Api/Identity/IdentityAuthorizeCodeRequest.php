<?php

namespace App\Http\Requests\Api\Identity;

use Illuminate\Foundation\Http\FormRequest;

class IdentityAuthorizeCodeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'auth_code' => 'required'
        ];
    }
}
