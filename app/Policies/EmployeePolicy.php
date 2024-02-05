<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\Identity;
use App\Models\Organization;
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
        return $organization->identityCan($identity, 'manage_employees');
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

        // organization owner employee can't be edited
        if ($employee->identity_address == $organization->identity_address) {
            return $this->deny("employees.cant_delete_organization_owner");
        }

        return $employee->organization->identityCan($identity, 'manage_employees');
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
        // organization owner employee can't be edited
        if ($employee->identity_address === $identity->address) {
            return $this->deny("employees.cant_delete_yourself");
        }

        return $this->update($identity, $employee, $organization);
    }
}
