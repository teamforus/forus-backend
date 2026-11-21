<?php

namespace App\Modules\RequesterImport\Policies;

use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use App\Modules\RequesterImport\Models\RequesterImportRow;

class RequesterImportRowPolicy
{
    /**
     * @param Identity $identity
     * @param RequesterImportRow $row
     * @param Organization $organization
     * @return bool
     */
    public function update(Identity $identity, RequesterImportRow $row, Organization $organization): bool
    {
        return $row->organization_id === $organization->id && $this->canManage($identity, $organization);
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
     * @param Organization $organization
     * @return bool
     */
    public function viewAny(Identity $identity, Organization $organization): bool
    {
        return $this->canManage($identity, $organization);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function sync(Identity $identity, Organization $organization): bool
    {
        return $this->canManage($identity, $organization);
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
            $organization->allow_requester_imports &&
            $organization->allow_profiles &&
            $organization->bsn_enabled &&
            $organization->identityCan($identity, Permission::MANAGE_IDENTITIES);
    }
}
