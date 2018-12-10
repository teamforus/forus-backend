<?php

namespace App\Listeners;

use App\Events\Organizations\OrganizationCreated;
use App\Events\Organizations\OrganizationUpdated;
use Illuminate\Events\Dispatcher;

class OrganizationSubscriber
{
    private $mailService;

    public function __construct()
    {
        $this->mailService = resolve('forus.services.mail_notification');
    }

    public function onOrganizationCreated(OrganizationCreated $organizationCreated) {
        $organization = $organizationCreated->getOrganization();

        $organization->validators()->create([
            'identity_address' => $organization->identity_address
        ]);

        $this->mailService->addEmailConnection(
            $organization->emailServiceId(),
            $organization->email
        );

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

    public function onOrganizationUpdated(OrganizationUpdated $organizationUpdated) {
        $organization = $organizationUpdated->getOrganization();

        $this->mailService->addEmailConnection(
            $organization->emailServiceId(),
            $organization->email
        );
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
