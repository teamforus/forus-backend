<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\Identity;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class FundPolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     */
    public function show(Identity $identity, Fund $fund, Organization $organization): bool
    {
        if ($fund->organization_id !== $organization->id) {
            return false;
        }

        return $fund->public || $fund->organization->identityCan($identity, [
            'manage_funds', 'view_finances', 'view_funds'
        ], false);
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewIdentitiesSponsor(
        Identity $identity,
        Fund $fund,
        Organization $organization
    ): bool {
        if ($fund->organization_id !== $organization->id) {
            return false;
        }

        return $fund->organization->identityCan($identity, [
            'manage_implementation_notifications', 'manage_vouchers'
        ], false);
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     */
    public function update(Identity $identity, Fund $fund, Organization $organization): bool
    {
        if ($fund->organization_id !== $organization->id) {
            return false;
        }

        return $fund->organization->identityCan($identity, 'manage_funds');
    }
}
