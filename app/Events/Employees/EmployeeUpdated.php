<?php

namespace App\Events\Employees;

use App\Models\Employee;

class EmployeeUpdated extends BaseEmployeeEvent
{
    protected $previous_roles;

    /**
     * Create a new event instance.
     *
     * EmployeeUpdated constructor.
     * @param Employee $employee
     * @param array $previous_roles
     */
    public function __construct(Employee $employee, array $previous_roles)
    {
        parent::__construct($employee);
        $this->previous_roles = $previous_roles;
    }

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
