<?php

namespace App\Http\Requests\Api\Platform\Employees;

use App\Http\Requests\BaseFormRequest;

class IndexEmployeesRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{per_page: 'nullable|numeric|between:1,100', role: 'nullable|exists:roles,key'}
     */
    public function rules(): array
    {
        return [
            'per_page' => 'nullable|numeric|between:1,100',
            'role' => 'nullable|exists:roles,key',
        ];
    }
}
