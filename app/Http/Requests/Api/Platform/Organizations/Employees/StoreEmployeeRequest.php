<?php

namespace App\Http\Requests\Api\Platform\Organizations\Employees;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\NotIn;

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
        $recordRepo = resolve('forus.services.record');
        $primaryEmail = $recordRepo->primaryEmailByAddress(auth()->id());

        return [
            'email'     => [
                'required',
                'email:strict,dns',
                new NotIn([$primaryEmail]),
            ],
            'roles'     => 'present|array',
            'roles.*'   => 'exists:roles,id',
            'target' => [
                'nullable',
                'alpha_dash',
            ],
        ];
    }

    public function messages()
    {
        return [
            'email.not_in' => trans('validation.owner_cant_be_employee')
        ];
    }
}
