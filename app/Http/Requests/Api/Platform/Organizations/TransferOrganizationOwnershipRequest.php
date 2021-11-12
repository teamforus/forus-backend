<?php

namespace App\Http\Requests\Api\Platform\Organizations;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

/**
 * Class TransferOrganizationOwnershipRequest
 * @property-read Organization $organization
 * @package App\Http\Requests\Api\Platform\Organizations\Employees
 */
class TransferOrganizationOwnershipRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->organization->identity_address === $this->auth_address();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where(function(Builder $builder) {
                    $adminEmployeesQuery = $this->organization->employeesOfRoleQuery('admin')->getQuery();

                    $builder->where('organization_id', $this->organization->id);
                    $builder->whereIn('id', $adminEmployeesQuery->select('employees.id'));
                })
            ],
        ];
    }
}
