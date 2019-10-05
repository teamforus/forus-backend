<?php

namespace App\Listeners;

use App\Events\Employees\EmployeeCreated;
use App\Events\Employees\EmployeeDeleted;
use App\Events\Employees\EmployeeUpdated;
use App\Models\Employee;
use Illuminate\Events\Dispatcher;

class EmployeeSubscriber
{
    private $mailService;
    private $recordService;

    /**
     * EmployeeSubscriber constructor.
     */
    public function __construct()
    {
        $this->mailService = resolve('forus.services.notification');
        $this->recordService = resolve('forus.services.record');
    }

    /**
     * @param EmployeeCreated $employeeCreated
     * @throws \Exception
     */
    public function onEmployeeCreated(EmployeeCreated $employeeCreated) {
        $employee = $employeeCreated->getEmployee();

        $this->updateValidatorEmployee($employee);

        $transData = [
            "org_name" => $employee->organization->name,
            "role_name_list" => $employee->roles->implode('name', ', '),
        ];

        $title = trans('push.access_levels.added.title', $transData);
        $body = trans('push.access_levels.added.body', $transData);

        $this->mailService->sendPushNotification(
            $employee->identity_address, $title, $body
        );
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

        $transData = [
            "org_name" => $employee->organization->name
        ];

        $title = trans('push.access_levels.removed.title', $transData);
        $body = trans('push.access_levels.removed.body', $transData);

        $this->mailService->sendPushNotification(
            $employee->identity_address, $title, $body
        );
    }

    /**
     * @param Employee $employee
     * @throws \Exception
     */
    private function updateValidatorEmployee(Employee $employee) {
        $organization = $employee->organization;
        $query = $employee->only('identity_address');

        if ($organization->identity_address == $employee->identity_address) {
            return;
        }

        if (!$employee->hasRole('validation')) {
            $organization->validators()->where([
                'identity_address' => $employee->identity_address
            ])->delete();
        } elseif ($organization->validators()->where($query)->count() == 0) {
            $organization->validators()->create($query);

            /*$this->mailService->youAddedAsValidator(
                $this->recordService->primaryEmailByAddress(
                    $employee->identity_address
                ),
                $employee->identity_address,
                $organization->name
            );*/
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
