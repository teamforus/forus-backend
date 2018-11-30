<?php

namespace App\Listeners;

use App\Events\Organizations\OrganizationCreated;
use Illuminate\Events\Dispatcher;

class OrganizationSubscriber
{
    public function onOrganizationCreated(OrganizationCreated $organizationCreated) {
        $organization = $organizationCreated->getOrganization();

        $organization->validators()->create([
            'identity_address' => $organization->identity_address
        ]);

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
    }
}
