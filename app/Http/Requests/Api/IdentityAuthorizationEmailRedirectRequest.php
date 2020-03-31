<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IdentityAuthorizationEmailRedirectRequest extends FormRequest
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
            'target' => [
                'nullable',
                'alpha_dash',
            ],
            'is_mobile' => env('DISABLE_DEPRECATED_API', false) ? [
                 'present',
                 Rule::in(1, 0)
            ] : [],
        ];
    }
}
