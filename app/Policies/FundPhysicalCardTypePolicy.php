<?php

namespace App\Policies;

use App\Models\FundConfig;
use App\Models\FundPhysicalCardType;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class FundPhysicalCardTypePolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function viewAny(Identity $identity, Organization $organization): bool
    {
        return $this->store($identity, $organization);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function store(Identity $identity, Organization $organization): bool
    {
        return $organization->allow_physical_cards && $organization->identityCan($identity, [
            Permission::VIEW_FUNDS,
            Permission::MANAGE_FUNDS,
        ], false);
    }

    /**
     * @param Identity $identity
     * @param FundPhysicalCardType $fundPhysicalCardType
     * @param Organization $organization
     * @return bool
     */
    public function update(
        Identity $identity,
        FundPhysicalCardType $fundPhysicalCardType,
        Organization $organization,
    ): bool {
        if ($fundPhysicalCardType->fund->organization_id !== $organization->id) {
            return false;
        }

        return $this->store($identity, $organization);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @param FundPhysicalCardType $fundPhysicalCardType
     * @return bool|Response
     */
    public function delete(
        Identity $identity,
        FundPhysicalCardType $fundPhysicalCardType,
        Organization $organization,
    ): bool|Response {
        if (!$this->update($identity, $fundPhysicalCardType, $organization)) {
            return false;
        }

        if (FundConfig::query()
            ->whereRelation('fund', 'organization_id', $organization->id)
            ->where('fund_request_physical_card_type_id', $fundPhysicalCardType->physical_card_type_id)
            ->exists()) {
            return $this->deny('Het passen type kan niet worden verwijderd van het fund terwijl het in gebruik is.');
        }

        return $this->store($identity, $organization);
    }
}
