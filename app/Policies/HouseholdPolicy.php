<?php

namespace App\Policies;

use App\Models\Household;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use Illuminate\Auth\Access\HandlesAuthorization;

class HouseholdPolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function viewAnyHousehold(Identity $identity, Organization $organization): bool
    {
        return $organization->allow_profiles_households && $organization->identityCan($identity, [
            Permission::VIEW_IDENTITIES,
            Permission::MANAGE_IDENTITIES,
        ], false);
    }

    /**
     * @param Identity $identity
     * @param Household $household
     * @param Organization $organization
     * @return bool
     */
    public function viewHousehold(Identity $identity, Household $household, Organization $organization): bool
    {
        return
            $household->organization_id === $organization->id &&
            $organization->identityCan($identity, [
                Permission::VIEW_IDENTITIES,
                Permission::MANAGE_IDENTITIES,
            ], false) &&
            $organization->allow_profiles_households;
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function createHousehold(Identity $identity, Organization $organization): bool
    {
        return
            $organization->identityCan($identity, Permission::MANAGE_IDENTITIES) &&
            $organization->allow_profiles_households;
    }

    /**
     * @param Identity $identity
     * @param Household $household
     * @param Organization $organization
     * @return bool
     */
    public function updateHousehold(Identity $identity, Household $household, Organization $organization): bool
    {
        return
            $household->organization_id === $organization->id &&
            $organization->identityCan($identity, Permission::MANAGE_IDENTITIES) &&
            $organization->allow_profiles_households;
    }

    /**
     * @param Identity $identity
     * @param Household $household
     * @param Organization $organization
     * @return bool
     */
    public function deleteHousehold(Identity $identity, Household $household, Organization $organization): bool
    {
        return
            $household->organization_id === $organization->id &&
            $organization->identityCan($identity, Permission::MANAGE_IDENTITIES) &&
            $organization->allow_profiles_households;
    }
}
