<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\Fund;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use App\Scopes\Builders\FundQuery;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class EmployeePolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function viewAny(Identity $identity, Organization $organization): bool
    {
        return $organization->findEmployee($identity)?->roles()->exists();
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function store(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, Permission::MANAGE_EMPLOYEES);
    }

    /**
     * @param Identity $identity
     * @param Employee $employee
     * @param Organization $organization
     * @return Response|bool
     */
    public function show(Identity $identity, Employee $employee, Organization $organization): Response|bool
    {
        return $this->update($identity, $employee, $organization);
    }

    /**
     * @param Identity $identity
     * @param Employee $employee
     * @param Organization $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function update(Identity $identity, Employee $employee, Organization $organization): Response|bool
    {
        if ($employee->organization_id != $organization->id) {
            return false;
        }

        return $employee->organization->identityCan($identity, Permission::MANAGE_EMPLOYEES);
    }

    /**
     * @param Identity $identity
     * @param Employee $employee
     * @param Organization $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function destroy(
        Identity $identity,
        Employee $employee,
        Organization $organization
    ): Response|bool {
        // employee can't delete himself
        if ($employee->identity_address === $identity->address) {
            return $this->deny('employees.cant_delete_yourself');
        }

        // organization owner employee can't be deleted
        if ($employee->identity_address === $organization->identity_address) {
            return $this->deny('employees.cant_delete_owner');
        }

        // employee can't be deleted if it used as default validator
        if (FundQuery::whereActiveFilter(Fund::where('default_validator_employee_id', $employee->id))->exists()) {
            return $this->deny(__('policies.employees.cant_delete_if_default_validator_exists'));
        }

        return $this->update($identity, $employee, $organization);
    }
}
