<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\PhysicalCardType;
use Illuminate\Auth\Access\HandlesAuthorization;

class PhysicalCardTypePolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function viewAny(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, [Permission::VIEW_VOUCHERS, Permission::MANAGE_VOUCHERS], false);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function store(Identity $identity, Organization $organization): bool
    {
        return
            $organization->allow_physical_cards &&
            $organization->identityCan($identity, Permission::MANAGE_VOUCHERS);
    }

    /**
     * @param Identity $identity
     * @param PhysicalCardType $physicalCardType
     * @param Organization $organization
     * @return bool
     */
    public function show(Identity $identity, PhysicalCardType $physicalCardType, Organization $organization): bool
    {
        if ($physicalCardType->organization_id !== $organization->id) {
            return false;
        }

        return
            $organization->allow_physical_cards &&
            $physicalCardType->organization->identityCan($identity, [
                Permission::VIEW_VOUCHERS, Permission::MANAGE_VOUCHERS,
            ], false);
    }

    /**
     * @param Identity $identity
     * @param PhysicalCardType $physicalCardType
     * @param Organization $organization
     * @return bool
     */
    public function update(Identity $identity, PhysicalCardType $physicalCardType, Organization $organization): bool
    {
        if ($physicalCardType->organization_id !== $organization->id) {
            return false;
        }

        return
            $organization->allow_physical_cards &&
            $physicalCardType->organization->identityCan($identity, Permission::MANAGE_VOUCHERS);
    }

    /**
     * @param Identity $identity
     * @param PhysicalCardType $physicalCardType
     * @param Organization $organization
     * @return bool
     */
    public function destroy(Identity $identity, PhysicalCardType $physicalCardType, Organization $organization): bool
    {
        if ($physicalCardType->organization_id !== $organization->id) {
            return false;
        }

        if (!$physicalCardType->organization->identityCan($identity, Permission::MANAGE_VOUCHERS)) {
            return false;
        }

        return
            $organization->allow_physical_cards &&
            $physicalCardType->funds()->doesntExist() &&
            $physicalCardType->physical_cards()->doesntExist();
    }
}
