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
    private $mailService;

    /**
     * EmployeeSubscriber constructor.
     */
    public function __construct()
    {
        $this->mailService = resolve('forus.services.notification');
    }

    /**
     * @param EmployeeCreated $employeeCreated
     * @throws \Exception
     */
    public function onEmployeeCreated(EmployeeCreated $employeeCreated): void {
        $employee = $employeeCreated->getEmployee();

        IdentityAddedEmployeeNotification::send($employee->log(Employee::EVENT_CREATED, [
            'employee' => $employee,
            'organization' => $employee->organization,
        ]));

        $transData = [
            "org_name" => $employee->organization->name,
            "role_name_list" => $employee->roles->implode('name', ', '),
        ];

        $this->mailService->sendPushNotification(
            $employee->identity_address,
            trans('push.access_levels.added.title', $transData),
            trans('push.access_levels.added.body', $transData),
            'employee.created'
        );
    }

    /**
     * @param EmployeeUpdated $employeeUpdated
     * @throws \Exception
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
                'employee_roles_assigned' => $removedRoles->pluck('name')->join(', '),
            ]);

            IdentityChangedEmployeeRolesNotification::send($event);
        }
    }

    /**
     * @param EmployeeDeleted $employeeDeleted
     * @throws \Exception
     */
    public function onEmployeeDeleted(EmployeeDeleted $employeeDeleted): void
    {
        $employee = $employeeDeleted->getEmployee();

        $event = $employee->log(Employee::EVENT_DELETED, [
            'employee' => $employee,
            'organization' => $employee->organization,
        ]);

        IdentityRemovedEmployeeNotification::send($event);

        $transData = [
            "org_name" => $employee->organization->name
        ];

        $title = trans('push.access_levels.removed.title', $transData);
        $body = trans('push.access_levels.removed.body', $transData);

        $this->mailService->sendPushNotification(
            $employee->identity_address, $title, $body, 'employee.deleted'
        );
    }

    /**
     * The events dispatcher
     *
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events): void
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
