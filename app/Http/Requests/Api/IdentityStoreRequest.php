<?php

namespace App\Http\Requests\Api;

use App\Rules\IdentityPinCodeRule;
use App\Rules\IdentityRecordsAddressRule;
use App\Rules\IdentityRecordsRule;
use App\Rules\IdentityRecordsUniqueRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        return [
            'pin_code'                  => ['required', new IdentityPinCodeRule()],
            'records'                   => ['required', 'array', new IdentityRecordsRule()],
            'records.primary_email'     => ['required', 'email', new IdentityRecordsUniqueRule('primary_email')],
            'records.address'           => [new IdentityRecordsAddressRule()],
            'records.*'                 => ['required'],
            'target' => [
                'nullable',
                'alpha_dash',
            ]
        ];
    }
}
