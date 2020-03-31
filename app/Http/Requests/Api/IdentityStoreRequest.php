<?php

namespace App\Http\Requests\Api;

use App\Rules\IdentityPinCodeRule;
use App\Rules\IdentityRecordsAddressRule;
use App\Rules\IdentityRecordsRule;
// use App\Rules\IdentityEmailUniqueRule;
use Illuminate\Foundation\Http\FormRequest;

class IdentityStoreRequest extends FormRequest
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
            'unique:identity_emails,email',
        ];

        return [
            'pin_code' => [
                'nullable',
                new IdentityPinCodeRule()
            ],
            'email' => array_merge((array) (
                $this->has('records.primary_email') ? 'nullable' : 'required'
            ), $emailRule),
            'records' => [
                !env('DISABLE_DEPRECATED_API', false) && !$this->has('email') ? 'required' : 'nullable',
                'array',
                new IdentityRecordsRule()
            ],
            'records.primary_email' => array_merge((array) (
                $this->has('email') ? 'nullable' : 'required'
            ), $emailRule),
            'records.address' => [
                new IdentityRecordsAddressRule()
            ],
            'records.*' => [
                'required'
            ],
            'target' => [
                'nullable',
                'alpha_dash',
            ]
        ];
    }
}
