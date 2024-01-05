<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class ImplementationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any implementations.
     *
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAny(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, [
            'manage_implementation', 'manage_implementation_cms',
        ], false);
    }

    /**
     * Determine whether the user can view the implementation.
     *
     * @param Identity $identity
     * @param Implementation $implementation
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function view(
        Identity $identity,
        Implementation $implementation,
        Organization $organization
    ): bool {
        if (!$this->checkIntegrity($implementation, $organization)) {
            return false;
        }

        return $organization->identityCan($identity, [
            'manage_implementation', 'manage_implementation_cms',
        ], false);
    }

    /**
     * Determine whether the user can update the implementation CMS.
     *
     * @param Identity $identity
     * @param Implementation $implementation
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function updateCMS(
        Identity $identity,
        Implementation $implementation,
        Organization $organization
    ): bool {
        if (!$this->checkIntegrity($implementation, $organization)) {
            return false;
        }

        return $organization->identityCan($identity, 'manage_implementation_cms');
    }

    /**
     * Determine whether the user can update the implementation.
     *
     * @param Identity $identity
     * @param Implementation $implementation
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function updateEmail(
        Identity $identity,
        Implementation $implementation,
        Organization $organization
    ): bool {
        if (!$this->checkIntegrity($implementation, $organization)) {
            return false;
        }

        return $organization->identityCan($identity, 'manage_implementation');
    }

    /**
     * Determine whether the user can update the implementation.
     *
     * @param Identity $identity
     * @param Implementation $implementation
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function updateEmailBranding(
        Identity $identity,
        Implementation $implementation,
        Organization $organization
    ): bool {
        if (!$this->checkIntegrity($implementation, $organization)) {
            return false;
        }

        return $organization->identityCan($identity, 'manage_implementation_cms');
    }

    /**
     * Determine whether the user can update the implementation.
     *
     * @param Identity $identity
     * @param Implementation $implementation
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function updateDigiD(
        Identity $identity,
        Implementation $implementation,
        Organization $organization
    ): bool {
        if (!$this->checkIntegrity($implementation, $organization)) {
            return false;
        }

        return $organization->identityCan($identity, 'manage_implementation');
    }

    /**
     * @param Identity $identity
     * @param Implementation $implementation
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function updatePreChecks(
        Identity $identity,
        Implementation $implementation,
        Organization $organization,
    ): bool {
        if (!$this->checkIntegrity($implementation, $organization)) {
            return false;
        }

        return $organization->identityCan($identity, 'manage_implementation') && $organization->allow_pre_checks;
    }

    /**
     * @param Implementation $implementation
     * @param Organization $organization
     * @return bool
     */
    private function checkIntegrity(
        Implementation $implementation,
        Organization $organization
    ): bool {
        return $implementation->organization_id === $organization->id;
    }
}
