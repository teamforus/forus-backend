<?php

namespace App\Policies;

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
     * @param string $identity_address
     * @param Implementation $implementation
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAny(
        string $identity_address,
        Implementation $implementation,
        Organization $organization
    ): bool {
        return
            $this->checkIntegrity($organization, $implementation) &&
            $organization->identityCan($identity_address, 'manage_implementation_cms');
    }

    /**
     * Determine whether the user can create implementation pages.
     *
     * @param string $identity_address
     * @param Implementation $implementation
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function create(
        string $identity_address,
        Implementation $implementation,
        Organization $organization
    ): bool {
        return
            $this->checkIntegrity($organization, $implementation) &&
            $organization->identityCan($identity_address, 'manage_implementation_cms');
    }

    /**
     * Determine whether the user can view the implementation page.
     *
     * @param string $identity_address
     * @param ImplementationPage $implementationPage
     * @param Implementation $implementation
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function view(
        string $identity_address,
        ImplementationPage $implementationPage,
        Implementation $implementation,
        Organization $organization
    ): bool {
        return
            $this->checkIntegrity($organization, $implementation, $implementationPage) &&
            $organization->identityCan($identity_address, 'manage_implementation_cms');
    }

    /**
     * Determine whether the user can update the implementation page.
     *
     * @param string $identity_address
     * @param ImplementationPage $implementationPage
     * @param Implementation $implementation
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function update(
        string $identity_address,
        ImplementationPage $implementationPage,
        Implementation $implementation,
        Organization $organization
    ): bool {
        return
            $this->checkIntegrity($organization, $implementation, $implementationPage) &&
            $organization->identityCan($identity_address, 'manage_implementation_cms');
    }

    /**
     * Determine whether the user can delete the implementation page.
     *
     * @param string $identity_address
     * @param ImplementationPage $implementationPage
     * @param Implementation $implementation
     * @param Organization $organization
     * @return bool
     */
    public function destroy(
        string $identity_address,
        ImplementationPage $implementationPage,
        Implementation $implementation,
        Organization $organization
    ): bool {
        return
            $this->checkIntegrity($organization, $implementation, $implementationPage) &&
            $organization->identityCan($identity_address, 'manage_implementation_cms');
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
