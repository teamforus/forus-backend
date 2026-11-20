<?php

namespace App\Modules\DCP\Policies;

use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use App\Modules\DCP\Models\DCPOrder;

class DCPOrderPolicy
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
     * @param DCPOrder $order
     * @param Organization $organization
     * @return bool
     */
    public function show(Identity $identity, DCPOrder $order, Organization $organization): bool
    {
        return $order->organization_id === $organization->id && $this->canManage($identity, $organization);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function create(Identity $identity, Organization $organization): bool
    {
        return
            $this->canManage($identity, $organization) &&
            $organization->allow_voucher_passes &&
            $organization->allow_profiles &&
            $organization->bsn_enabled &&
            $organization->dcp_connection_id !== null;
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
