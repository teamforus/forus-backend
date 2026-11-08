<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use App\Services\DCPService\Models\DCPOrderBulk;
use Illuminate\Auth\Access\HandlesAuthorization;

class DCPOrderBulkPolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAny(Identity $identity, Organization $organization): bool
    {
        return
            $organization->allow_physical_cards &&
            $organization->identityCan($identity, [Permission::MANAGE_VOUCHERS, Permission::VIEW_VOUCHERS], false);
    }

    /**
     * @param Identity $identity
     * @param DCPOrderBulk $bulk
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function show(Identity $identity, DCPOrderBulk $bulk, Organization $organization): bool
    {
        return $bulk->organization_id === $organization->id && $this->viewAny($identity, $organization);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function store(Identity $identity, Organization $organization): bool
    {
        return $organization->allow_physical_cards && $organization->identityCan($identity, Permission::MANAGE_VOUCHERS);
    }
}
