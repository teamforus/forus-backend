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
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where(function(Builder $builder) {
                    $employeesQuery = $this->organization->employeesOfRoleQuery('admin');

                    $builder->where('organization_id', $this->organization->id);
                    $builder->addWhereExistsQuery($employeesQuery->getBaseQuery());
                })
            ],
        ];
    }
}
