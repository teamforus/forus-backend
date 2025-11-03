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
     * Determine whether the user can view any reimbursement category.
     *
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function viewAny(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, 'manage_reimbursements');
    }

    /**
     * Determine whether the user can add new reimbursement category.
     *
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function store(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, 'manage_reimbursements');
    }

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
     * Determine whether the user can view the reimbursement category.
     *
     * @param Identity $identity
     * @param ReimbursementCategory $reimbursementCategory
     * @param Organization $organization
     * @return bool
     */
    public function view(
        Identity $identity,
        ReimbursementCategory $reimbursementCategory,
        Organization $organization
    ): bool {
        return $this->update($identity, $reimbursementCategory, $organization);
    }

    /**
     * Determine whether the user can delete the reimbursement category.
     *
     * @param Identity $identity
     * @param ReimbursementCategory $reimbursementCategory
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function destroy(
        Identity $identity,
        ReimbursementCategory $reimbursementCategory,
        Organization $organization
    ): bool {
        return
            $reimbursementCategory->reimbursements()->doesntExist() &&
            $this->update($identity, $reimbursementCategory, $organization);
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
