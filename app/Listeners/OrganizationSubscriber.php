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
    public function onOrganizationCreated(OrganizationCreated $organizationCreated) {
        $organization = $organizationCreated->getOrganization();

        /** @var Employee $employee */
        $employee = $organization->employees()->firstOrCreate([
            'identity_address' => $organization->identity_address
        ]);

        $employee->roles()->sync(Role::pluck('id'));

        $organization->update([
            'description_text' => $organization->descriptionToText(),
        ]);

        try {
            $kvkService = resolve('kvk_api');

            if ($organization->kvk != Organization::GENERIC_KVK) {
                $offices = $kvkService->getOffices($organization->kvk);

                foreach (collect($offices ?: []) as $office) {
                    $organization->offices()->create(
                        collect($office)->only([
                            'address', 'lon', 'lat'
                        ])->toArray()
                    );
                }
            }
        } catch (\Exception $e) { }
    }

    /**
     * @param OrganizationUpdated $organizationUpdated
     */
    public function onOrganizationUpdated(OrganizationUpdated $organizationUpdated) {
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
