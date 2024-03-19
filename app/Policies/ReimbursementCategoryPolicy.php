<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Organization;
use App\Models\ReimbursementCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReimbursementCategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can update the reimbursement category.
     *
     * @param Identity $identity
     * @param ReimbursementCategory $reimbursementCategory
     * @param Organization $organization
     * @return bool
     */
    public function update(
        Identity $identity,
        ReimbursementCategory $reimbursementCategory,
        Organization $organization
    ): bool {
        if (!$this->checkIntegrity($reimbursementCategory, $organization)) {
            return false;
        }

        return $organization->identityCan($identity, 'manage_reimbursements');
    }

    /**
     * @param ReimbursementCategory $reimbursementCategory
     * @param Organization $organization
     * @return bool
     */
    private function checkIntegrity(ReimbursementCategory $reimbursementCategory, Organization $organization): bool
    {
        return $reimbursementCategory->organization_id === $organization->id;
    }
}
