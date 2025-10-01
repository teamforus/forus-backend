<?php

namespace App\Policies;

use App\Models\Household;
use App\Models\HouseholdProfile;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use Illuminate\Auth\Access\HandlesAuthorization;

class HouseholdIdentitiesPolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param Household $household
     * @param Organization $organization
     * @return bool
     */
    public function viewAnyHouseholdProfile(
        Identity $identity,
        Household $household,
        Organization $organization,
    ): bool {
        if ($household->organization_id !== $organization->id) {
            return false;
        }

        return $organization->identityCan($identity, [
            Permission::VIEW_IDENTITIES,
            Permission::MANAGE_IDENTITIES,
        ]);
    }

    /**
     * @param Identity $identity
     * @param Household $household
     * @param Organization $organization
     * @return bool
     */
    public function createHouseholdIdentity(
        Identity $identity,
        Household $household,
        Organization $organization,
    ): bool {
        if ($household->organization_id !== $organization->id) {
            return false;
        }

        return $organization->identityCan($identity, Permission::MANAGE_IDENTITIES);
    }

    /**
     * @param Identity $identity
     * @param HouseholdProfile $householdProfile
     * @param Household $household
     * @param Organization $organization
     * @return bool
     */
    public function deleteHouseholdProfile(
        Identity $identity,
        HouseholdProfile $householdProfile,
        Household $household,
        Organization $organization,
    ): bool {
        if ($household->organization_id !== $organization->id) {
            return false;
        }

        if ($householdProfile->household_id !== $household->id) {
            return false;
        }

        return $organization->identityCan($identity, Permission::MANAGE_IDENTITIES);
    }
}
