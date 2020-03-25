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
        $emailRule = [
            'email:strict,dns',
            new IdentityEmailExistsRule()
        ];

        return array_merge(env('DISABLE_DEPRECATED_API', false) ? [
            'email' => array_merge((array) 'required', $emailRule),
        ] : [
            'email' => array_merge((array) (
                $this->has('primary_email') ? 'nullable' : 'required'
            ), $emailRule),
            'primary_email' => array_merge((array) (
                $this->has('email') ? 'nullable' : 'required'
            ), $emailRule),
        ], [
            'source' => env('DISABLE_DEPRECATED_API', false) ? [] : [
                'required',
                'in:' . Implementation::keysAvailable()->implode(',')
            ],
            'target' => [
                'nullable',
                'alpha_dash',
            ]
        ]);
    }
}
