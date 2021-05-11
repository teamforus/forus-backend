<?php

namespace App\Http\Requests\Api\Platform\Organizations\Employees;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class TransferOrganizationOwnershipRequest
 * @property-read Organization $organization
 * @package App\Http\Requests\Api\Platform\Organizations\Employees
 */
class TransferOrganizationOwnershipRequest extends FormRequest
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
        $employees = $this->organization->employees()->pluck('employees.id');

        return [
            'to_employee' => 'required|exists:employees,id|in:' . $employees->join(','),
        ];
    }
}
