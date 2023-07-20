<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrganizationContactPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any bank connection.
     *
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function viewAny(Identity $identity, Organization $organization): bool
    {
        return $identity->address === $organization->identity_address;
    }

    /**
     * Determine whether the user can add new bank connection.
     *
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function store(Identity $identity, Organization $organization): bool
    {
        return $identity->address === $organization->identity_address;
    }
}
