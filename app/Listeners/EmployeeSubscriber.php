<?php

namespace App\Listeners;

use App\Events\Employees\EmployeeCreated;
use App\Events\Employees\EmployeeDeleted;
use App\Events\Employees\EmployeeUpdated;
use App\Models\Employee;
use Illuminate\Events\Dispatcher;

class EmployeeSubscriber
{
    /**
     * @param EmployeeCreated $employeeCreated
     * @throws \Exception
     */
    public function onEmployeeCreated(EmployeeCreated $employeeCreated) {
        $this->updateValidatorEmployee($employeeCreated->getEmployee());
    }

    /**
     * @param EmployeeUpdated $employeeUpdated
     * @throws \Exception
     */
    public function onEmployeeUpdated(EmployeeUpdated $employeeUpdated) {
        $this->updateValidatorEmployee($employeeUpdated->getEmployee());
    }

    /**
     * @param EmployeeDeleted $employeeDeleted
     * @throws \Exception
     */
    public function onEmployeeDeleted(EmployeeDeleted $employeeDeleted) {
        $employee = $employeeDeleted->getEmployee();

        $employee->organization->validators()->where([
            'identity_address' => $employee->identity_address
        ])->delete();
    }

    /**
     * @param Employee $employee
     * @throws \Exception
     */
    private function updateValidatorEmployee(Employee $employee) {
        if (
            $employee->organization->identity_address ==
            $employee->identity_address
        ) {
            return;
        }

        if ($employee->hasRole('validation')) {
            $employee->organization->validators()->firstOrCreate([
                'identity_address' => $employee->identity_address
            ]);
        } else {
            $employee->organization->validators()->where([
                'identity_address' => $employee->identity_address
            ])->delete();
        }
    }

    /**
     * The events dispatcher
     *
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(
            EmployeeCreated::class,
            '\App\Listeners\EmployeeSubscriber@onEmployeeCreated'
        );

        $events->listen(
            EmployeeUpdated::class,
            '\App\Listeners\EmployeeSubscriber@onEmployeeUpdated'
        );

        $events->listen(
            EmployeeDeleted::class,
            '\App\Listeners\EmployeeSubscriber@onEmployeeDeleted'
        );
    }
}
