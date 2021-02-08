<?php

namespace App\Http\Requests\Api\Platform\Organizations\Employees;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\NotIn;

/**
 * Class StoreEmployeeRequest
 * @property Organization $organization
 * @package App\Http\Requests\Api\Platform\Organizations\Employees
 */
class StoreEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $employees = $this->organization->employees->pluck('identity_address');
        $employees->push($this->organization->identity_address);

        $emails = $employees->map(static function($identity_address) {
            return record_repo()->primaryEmailByAddress($identity_address);
        })->toArray();

        return [
            'email'     => [
                'required',
                'email:strict',
                new NotIn($emails),
            ],
            'roles'     => 'present|array',
            'roles.*'   => 'exists:roles,id',
            'target' => [
                'nullable',
                'alpha_dash',
            ],
        ];
    }

    /**
     * @return array
     */
    public function messages(): array
    {
        return [
            'email.not_in' => trans('validation.employees.employee_already_exists')
        ];
    }
}
