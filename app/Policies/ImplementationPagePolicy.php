<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Implementation;
use App\Models\ImplementationPage;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class ImplementationPagePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any implementation pages.
     *
     * @param Identity $identity
     * @param Implementation $implementation
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAny(
        Identity $identity,
        Implementation $implementation,
        Organization $organization
    ): bool {
        return
            $this->checkIntegrity($organization, $implementation) &&
            $organization->identityCan($identity, 'manage_implementation_cms');
    }

    /**
     * Determine whether the user can create implementation pages.
     *
     * @param Identity $identity
     * @param Implementation $implementation
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function create(
        Identity $identity,
        Implementation $implementation,
        Organization $organization
    ): bool {
        return
            $this->checkIntegrity($organization, $implementation) &&
            $organization->identityCan($identity, 'manage_implementation_cms');
    }

    /**
     * Determine whether the user can view the implementation page.
     *
     * @param Identity $identity
     * @param ImplementationPage $implementationPage
     * @param Implementation $implementation
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function view(
        Identity $identity,
        ImplementationPage $implementationPage,
        Implementation $implementation,
        Organization $organization
    ): bool {
        return
            $this->checkIntegrity($organization, $implementation, $implementationPage) &&
            $organization->identityCan($identity, 'manage_implementation_cms');
    }

    /**
     * Determine whether the user can update the implementation page.
     *
     * @param Identity $identity
     * @param ImplementationPage $implementationPage
     * @param Implementation $implementation
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function update(
        Identity $identity,
        ImplementationPage $implementationPage,
        Implementation $implementation,
        Organization $organization
    ): bool {
        return
            $this->checkIntegrity($organization, $implementation, $implementationPage) &&
            $organization->identityCan($identity, 'manage_implementation_cms');
    }

    /**
     * Determine whether the user can delete the implementation page.
     *
     * @param Identity $identity
     * @param ImplementationPage $implementationPage
     * @param Implementation $implementation
     * @param Organization $organization
     * @return bool
     */
    public function destroy(
        Identity $identity,
        ImplementationPage $implementationPage,
        Implementation $implementation,
        Organization $organization
    ): bool {
        return
            $this->checkIntegrity($organization, $implementation, $implementationPage) &&
            $organization->identityCan($identity, 'manage_implementation_cms');
    }

    /**
     * @param Organization $organization
     * @param Implementation $implementation
     * @param ImplementationPage|null $implementationPage
     * @return bool
     */
    private function checkIntegrity(
        Organization $organization,
        Implementation $implementation,
        ?ImplementationPage $implementationPage = null,
    ): bool {
        $implementationIntegrity =
            $implementation->organization_id === $organization->id;

        $implementationPageIntegrity =
            $implementationPage === null ||
            $implementationPage->implementation_id === $implementation->id;

        return $implementationIntegrity && $implementationPageIntegrity;
    }
}
