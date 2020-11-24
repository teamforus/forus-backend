<?php

namespace App\Http\Requests\Api\Platform\Organizations\Employees;

use Illuminate\Foundation\Http\FormRequest;

class IndexEmployeesRequest extends FormRequest
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
            'per_page'  => 'numeric|between:1,100',
            'role' => [
                'nullable', 'string', 'exists:roles,key'
            ]
        ];
    }
}
