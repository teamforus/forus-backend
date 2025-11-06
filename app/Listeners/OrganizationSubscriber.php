<?php

namespace App\Listeners;

use App\Events\Organizations\OrganizationCreated;
use App\Events\Organizations\OrganizationUpdated;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Role;
use Illuminate\Events\Dispatcher;
use League\CommonMark\Exception\CommonMarkException;
use Throwable;

class OrganizationSubscriber
{
    /**
     * @param OrganizationCreated $organizationCreated
     * @throws CommonMarkException
     * @return void
     * @noinspection PhpUnused
     */
    public function onOrganizationCreated(OrganizationCreated $organizationCreated): void
    {
        /** @var Employee $employee */
        $organization = $organizationCreated->getOrganization();
        $employee = $organization->employees()->firstOrCreate($organization->only('identity_address'));

        $employee->roles()->sync(Role::pluck('id'));
        $organization->syncMarkdownTexts();

        try {
            if ($organization->kvk != Organization::GENERIC_KVK) {
                foreach (resolve('kvk_api')->getOffices($organization->kvk) as $office) {
                    $organization->offices()->create(array_only($office, ['address', 'lon', 'lat']));
                }
            }
        } catch (Throwable) {
        }
    }

    /**
     * @param OrganizationUpdated $organizationUpdated
     * @noinspection PhpUnused
     * @throws CommonMarkException
     */
    public function onOrganizationUpdated(OrganizationUpdated $organizationUpdated): void
    {
        $organizationUpdated->getOrganization()->syncMarkdownTexts();
    }

    /**
     * The events' dispatcher.
     *
     * @param Dispatcher $events
     * @noinspection PhpUnused
     */
    public function subscribe(Dispatcher $events): void
    {
        $class = '\\' . static::class;

        $events->listen(OrganizationCreated::class, "$class@onOrganizationCreated");
        $events->listen(OrganizationUpdated::class, "$class@onOrganizationUpdated");
    }
}
