<?php

namespace App\Http\Requests\Api\Identity;

use App\Rules\IdentityEmailUniqueRule;
use Illuminate\Foundation\Http\FormRequest;

class IdentityEmailStoreRequest extends FormRequest
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
            'email' => [
                'required', new IdentityEmailUniqueRule()
            ]
        ];
    }
}
