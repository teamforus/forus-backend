<?php

namespace App\Modules\VoucherPass\Policies;

use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use App\Modules\VoucherPass\Models\VoucherPass;

class VoucherPassPolicy
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
     * @param VoucherPass $voucherPass
     * @param Organization $organization
     * @return bool
     */
    public function view(Identity $identity, VoucherPass $voucherPass, Organization $organization): bool
    {
        return $voucherPass->organization_id === $organization->id && $this->canManage($identity, $organization);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function create(Identity $identity, Organization $organization): bool
    {
        return $this->canManage($identity, $organization);
    }

    /**
     * @param Identity $identity
     * @param VoucherPass $voucherPass
     * @param Organization $organization
     * @return bool
     */
    public function update(Identity $identity, VoucherPass $voucherPass, Organization $organization): bool
    {
        return $voucherPass->organization_id === $organization->id && $this->canManage($identity, $organization);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    private function canManage(Identity $identity, Organization $organization): bool
    {
        return
            $organization->is_sponsor &&
            $organization->allow_voucher_passes &&
            $organization->allow_profiles &&
            $organization->bsn_enabled &&
            $organization->identityCan($identity, Permission::MANAGE_IDENTITIES);
    }
}
