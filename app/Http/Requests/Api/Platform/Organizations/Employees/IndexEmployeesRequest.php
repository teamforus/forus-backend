<?php

namespace App\Http\Requests\Api\Platform\Organizations\Employees;

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
            'q' => 'nullable|string|max:500',
            'per_page'  => 'numeric|int|between:1,100',
            'role' => 'nullable|string|exists:roles,key',
            'roles' => 'nullable|array',
            'roles.*' => 'nullable|string|exists:roles,key',
            'permission' => 'nullable|string|exists:permissions,key',
            'permissions' => 'nullable|array',
            'permissions.*' => 'nullable|exists:permissions,key',
        ];
    }
}
