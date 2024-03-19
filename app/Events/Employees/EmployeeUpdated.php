<?php

namespace App\Events\Employees;

use App\Models\Employee;

class EmployeeUpdated extends BaseEmployeeEvent
{
    protected $previous_roles;

    /**
     * Get employee roles before update
     *
     * @return array
     */
    public function getPreviousRoles(): array
    {
        return $this->previous_roles;
    }
}
