<?php

namespace App\Policies;

use App\Models\FundForm;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use Illuminate\Auth\Access\HandlesAuthorization;

class FundFormPolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function viewAny(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, Permission::MANAGE_FUNDS);
    }

    /**
     * @param Identity $identity
     * @param FundForm $fundForm
     * @param Organization $organization
     * @return bool
     */
    public function show(Identity $identity, FundForm $fundForm, Organization $organization): bool
    {
        return
            $fundForm->fund->organization_id === $organization->id &&
            $organization->identityCan($identity, Permission::MANAGE_FUNDS);
    }
}
