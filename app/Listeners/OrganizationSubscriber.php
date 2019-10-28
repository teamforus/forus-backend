<?php

namespace App\Listeners;

use App\Events\Employees\EmployeeCreated;
use App\Events\Organizations\OrganizationCreated;
use App\Events\Organizations\OrganizationUpdated;
use App\Models\Employee;
use App\Models\Role;
use Illuminate\Events\Dispatcher;

class OrganizationSubscriber
{
    private $mailService;

    public function __construct()
    {
        $this->mailService = resolve('forus.services.notification');
    }

    public function onOrganizationCreated(OrganizationCreated $organizationCreated) {
        $organization = $organizationCreated->getOrganization();

        $organization->validators()->create([
            'identity_address' => $organization->identity_address
        ]);

        /** @var Employee $employee */
        $employee = $organization->employees()->firstOrCreate([
            'identity_address' => $organization->identity_address
        ]);

        $employee->roles()->sync(Role::pluck('id'));

        try {
            $offices = resolve('kvk_api')->getOffices($organization->kvk);

            foreach (collect($offices ?: []) as $office) {
                $organization->offices()->create(
                    collect($office)->only([
                        'address', 'lon', 'lat'
                    ])->toArray()
                );
            }
        } catch (\Exception $e) { }
    }

    public function onOrganizationUpdated(OrganizationUpdated $organizationUpdated) {}

    /**
     * The events dispatcher
     *
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(
            OrganizationCreated::class,
            '\App\Listeners\OrganizationSubscriber@onOrganizationCreated'
        );

        $events->listen(
            OrganizationUpdated::class,
            '\App\Listeners\OrganizationSubscriber@onOrganizationUpdated'
        );
    }
}
