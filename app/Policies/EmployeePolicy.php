<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeePolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function index(
        $identity_address,
        Organization $organization
    ) {
        return $organization->identityCan(
            $identity_address,
            'manage_employees'
        );
    }

    /**
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function store(
        $identity_address,
        Organization $organization
    ) {
        return $organization->identityCan(
            $identity_address,
            'manage_employees'
        );
    }

    /**
     * @param $identity_address
     * @param Employee $employee
     * @param Organization $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        $identity_address,
        Employee $employee,
        Organization $organization
    ) {
        return $this->update($identity_address, $employee, $organization);
    }

    /**
     * @param $identity_address
     * @param Employee $employee
     * @param Organization $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        $identity_address,
        Employee $employee,
        Organization $organization
    ) {
        if ($employee->organization_id != $organization->id) {
            return false;
        }

        // organization owner employee can't be edited
        if ($employee->identity_address == $organization->identity_address) {
            $this->deny("employees.cant_delete_organization_owner");
        }

        return $employee->organization->identityCan(
            $identity_address,
            'manage_employees'
        );
    }

    /**
     * @param $identity_address
     * @param Employee $employee
     * @param Organization $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(
        $identity_address,
        Employee $employee,
        Organization $organization
    ) {
        return $this->update($identity_address, $employee, $organization);
    }
}
