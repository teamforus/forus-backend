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
     * @param Employee $employee
     * @param Organization $organization
     */
    public function update(Identity $identity, Employee $employee, Organization $organization): bool|bool
    {
        if ($employee->organization_id != $organization->id) {
            return false;
        }

        return $employee->organization->identityCan($identity, 'manage_employees');
    }
}
