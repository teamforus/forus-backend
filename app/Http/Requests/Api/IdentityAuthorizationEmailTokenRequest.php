<?php

namespace App\Http\Requests\Api;

use App\Models\Implementation;
use App\Rules\IdentityEmailExistsRule;
use Illuminate\Foundation\Http\FormRequest;

class IdentityAuthorizationEmailTokenRequest extends FormRequest
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
            'primary_email' => [
                'required',
                'email:strict,dns',
                new IdentityEmailExistsRule()
            ],
            'source' => env('DISABLE_DEPRECATED_API', false) ? [] : [
                'required',
                'in:' . Implementation::keysAvailable()->implode(',')
            ],
            'target' => [
                'nullable',
                'alpha_dash',
            ]
        ];
    }
}
