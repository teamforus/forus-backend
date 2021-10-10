<?php

namespace App\Listeners;

use App\Events\Organizations\OrganizationCreated;
use App\Events\Organizations\OrganizationUpdated;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Role;
use Illuminate\Events\Dispatcher;

/**
 * Class OrganizationSubscriber
 * @package App\Listeners
 */
class OrganizationSubscriber
{
    public function onOrganizationCreated(OrganizationCreated $organizationCreated): void
    {
        /** @var Employee $employee */
        $organization = $organizationCreated->getOrganization();
        $employee = $organization->employees()->firstOrCreate($organization->only('identity_address'));

        $employee->roles()->sync(Role::pluck('id'));

        $organization->update([
            'description_text' => $organization->descriptionToText(),
        ]);

        try {
            if ($organization->kvk != Organization::GENERIC_KVK) {
                foreach (resolve('kvk_api')->getOffices($organization->kvk) as $office) {
                    $organization->offices()->create($office->only('address', 'lon', 'lat'));
                }
            }
        } catch (\Exception $e) { }
    }

    /**
     * @param OrganizationUpdated $organizationUpdated
     */
    public function onOrganizationUpdated(OrganizationUpdated $organizationUpdated): void
    {
        $organization = $organizationUpdated->getOrganization();

        $organization->update([
            'description_text' => $organization->descriptionToText(),
        ]);
    }

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
