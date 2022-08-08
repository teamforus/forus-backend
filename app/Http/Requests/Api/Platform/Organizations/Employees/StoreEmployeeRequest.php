<?php

namespace App\Http\Requests\Api\Platform\Organizations\Employees;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;

/**
 * Class StoreEmployeeRequest
 * @property Organization $organization
 * @package App\Http\Requests\Api\Platform\Organizations\Employees
 */
class StoreEmployeeRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->isAuthenticated();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $employees = $this->organization->employees->load('identity.primary_email');
        $emails = $employees->pluck('identity.primary_email.email');
        $emails->push($this->organization->identity?->email);

        return [
            'email' => 'required|email:strict|not_in:' . $emails->filter()->join(','),
            'roles' => 'present|array',
            'roles.*' => 'exists:roles,id',
            'target' => 'nullable|alpha_dash',
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
