<?php

namespace App\Http\Requests\Api\Platform\Organizations\Employees;

class UpdateEmployeeRequest extends BaseEmployeeRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            ...$this->updateRules(),
        ];
    }
}
