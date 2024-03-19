<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Identity;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class FundProviderPolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param FundProvider $organizationFund
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function updateProvider(
        Identity $identity,
        FundProvider $organizationFund,
        Organization $organization
    ): bool {
        if ($organization->id != $organizationFund->organization_id) {
            return false;
        }

        return $organizationFund->organization->identityCan($identity, 'manage_provider_funds');
    }
}
