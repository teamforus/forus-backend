<?php

namespace App\Http\Requests\Api\Platform\Organizations\Employees;

use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends BaseEmployeeRequest
{
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
            'email' => [
                'required',
                Rule::notIn($emails->filter()->values()->toArray()),
                ...$this->emailRules(),
            ],
            'target' => 'nullable|alpha_dash',
            ...$this->updateRules(),
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
