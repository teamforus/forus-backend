<?php

namespace App\Modules\DCP\Policies;

use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use App\Modules\DCP\Models\DCPOrderBulk;

class DCPOrderBulkPolicy
{
    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function viewAny(Identity $identity, Organization $organization): bool
    {
        return $this->canManage($identity, $organization);
    }

    /**
     * @param Identity $identity
     * @param DCPOrderBulk $bulk
     * @param Organization $organization
     * @return bool
     */
    public function show(Identity $identity, DCPOrderBulk $bulk, Organization $organization): bool
    {
        return $bulk->organization_id === $organization->id && $this->canManage($identity, $organization);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function store(Identity $identity, Organization $organization): bool
    {
        return $this->canManage($identity, $organization) && $organization->dcp_connection?->canSend();
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    private function canManage(Identity $identity, Organization $organization): bool
    {
        return $organization->is_sponsor && $organization->identityCan($identity, Permission::MANAGE_IDENTITIES);
    }
}
