<?php

namespace App\Http\Requests\Api\Platform\Organizations\Employees;

use App\Rules\IdentityRecordsExistsRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
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
            'email'     => ['required', new IdentityRecordsExistsRule('primary_email')],
            'roles'     => 'present|array',
            'roles.*'   => 'exists:roles,id',
        ];
    }
}
