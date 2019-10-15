<?php

namespace App\Http\Requests\Api\Platform\Organizations\Employees;

use App\Rules\IdentityRecordsExistsRule;
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
                'email',
                new NotIn([$primaryEmail]),
            ],
            'roles'     => 'present|array',
            'roles.*'   => 'exists:roles,id',
        ];
    }

    public function messages()
    {
        return [
            'email.not_in' => trans('validation.owner_cant_be_employee')
        ];
    }
}
