<?php

namespace App\Listeners;

use App\Events\Employees\EmployeeCreated;
use App\Events\Employees\EmployeeDeleted;
use App\Events\Employees\EmployeeUpdated;
use App\Models\Employee;
use App\Models\Role;
use App\Notifications\Identities\Employee\IdentityChangedEmployeeRolesNotification;
use App\Notifications\Identities\Employee\IdentityAddedEmployeeNotification;
use App\Notifications\Identities\Employee\IdentityRemovedEmployeeNotification;
use Illuminate\Events\Dispatcher;

class EmployeeSubscriber
{
    /**
     * @param EmployeeCreated $employeeCreated
     * @throws \Exception
     * @noinspection PhpUnused
     */
    public function onEmployeeCreated(EmployeeCreated $employeeCreated): void
    {
        $employee = $employeeCreated->getEmployee();

        IdentityAddedEmployeeNotification::send($employee->log(Employee::EVENT_CREATED, [
            'employee' => $employee,
            'organization' => $employee->organization,
        ]));
    }

    /**
     * @param EmployeeUpdated $employeeUpdated
     * @throws \Exception
     * @noinspection PhpUnused
     */
    public function onEmployeeUpdated(EmployeeUpdated $employeeUpdated): void
    {
        $employee = $employeeUpdated->getEmployee();
        $currentRoles = $employee->roles->pluck('key')->toArray();
        $previousRoles = $employeeUpdated->getPreviousRoles();

        $removedRoles = array_filter($previousRoles, static function($role) use ($currentRoles) {
            return !in_array($role, $currentRoles, true);
        });

        $newRoles = array_filter($currentRoles, static function($role) use ($previousRoles) {
            return !in_array($role, $previousRoles, true);
        });

        $removedRoles = Role::whereIn('key', $removedRoles)->get();
        $assignedRoles = Role::whereIn('key', $newRoles)->get();

        if ($removedRoles->count() > 0 || $assignedRoles->count() > 0) {
            $event = $employee->log(Employee::EVENT_UPDATED, [
                'employee' => $employee,
                'organization' => $employee->organization,
            ], [
                'employee_roles_removed' => $removedRoles->pluck('name')->join(', '),
                'employee_roles_assigned' => $assignedRoles->pluck('name')->join(', '),
            ]);

            IdentityChangedEmployeeRolesNotification::send($event);
        }
    }

    /**
     * @param EmployeeDeleted $employeeDeleted
     * @throws \Exception
     * @noinspection PhpUnused
     */
    public function onEmployeeDeleted(EmployeeDeleted $employeeDeleted): void
    {
        $employee = $employeeDeleted->getEmployee();

        IdentityRemovedEmployeeNotification::send($employee->log(Employee::EVENT_DELETED, [
            'employee' => $employee,
            'organization' => $employee->organization,
        ]));
    }

    /**
     * The events dispatcher
     *
     * @param Dispatcher $events
     * @noinspection PhpUnused
     */
    public function subscribe(Dispatcher $events): void
    {
        $class = '\\' . static::class;

        $events->listen(EmployeeCreated::class, "$class@onEmployeeCreated");
        $events->listen(EmployeeUpdated::class, "$class@onEmployeeUpdated");
        $events->listen(EmployeeDeleted::class, "$class@onEmployeeDeleted");
    }
}
