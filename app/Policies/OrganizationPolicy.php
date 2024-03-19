<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\Fund;
use App\Models\Identity;
use App\Models\Organization;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\OrganizationQuery;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;

class OrganizationPolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function update(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, 'manage_organization');
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function listSponsorProviders(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, [
            'manage_providers', 'view_finances'
        ], false);
    }
}
