<?php

namespace App\Http\Requests\Api\Platform\Employees;

use App\Http\Requests\BaseFormRequest;

class IndexEmployeesRequest extends BaseFormRequest
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
        return [
            'per_page' => 'nullable|numeric|between:1,100',
            'role' => 'nullable|exists:roles,key',
        ];
    }
}
