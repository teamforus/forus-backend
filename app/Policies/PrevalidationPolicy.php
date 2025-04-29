<?php

namespace App\Policies;

use App\Exceptions\AuthorizationJsonException;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Prevalidation;
use App\Models\Voucher;
use App\Scopes\Builders\PrevalidationQuery;
use App\Scopes\Builders\VoucherQuery;

class PrevalidationPolicy extends BasePolicy
{
    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function viewAny(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, [
            Permission::VALIDATE_RECORDS,
            Permission::MANAGE_ORGANIZATION,
        ], false);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function create(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, [
            Permission::VALIDATE_RECORDS,
            Permission::MANAGE_ORGANIZATION,
        ], false);
    }

    /**
     * @param Identity $identity
     * @param Prevalidation $prevalidation
     * @throws AuthorizationJsonException
     * @return bool
     * @noinspection PhpUnused
     */
    public function redeem(Identity $identity, Prevalidation $prevalidation): bool
    {
        if (!$identity->exists) {
            return false;
        }

        if (!$prevalidation->exists()) {
            $this->deny('wrong_code');
        }

        if ($prevalidation->state !== $prevalidation::STATE_PENDING) {
            $this->deny('used');
        }

        if (VoucherQuery::whereNotExpired(Voucher::where([
            'fund_id' => $prevalidation->fund_id,
            'identity_id' => $identity->id,
        ]))->exists()) {
            $this->deny('used_same_fund');
        }

        return true;
    }

    public function destroy(Identity $identity, Prevalidation $prevalidation, Organization $organization): bool
    {
        return $this->canManagePrevalidation($identity, $prevalidation, $organization);
    }

    /**
     * Determine if the identity can manage the given prevalidation.
     *
     * @param Identity $identity
     * @param Prevalidation $prevalidation
     * @param Organization $organization
     * @return bool
     */
    private function canManagePrevalidation(
        Identity $identity,
        Prevalidation $prevalidation,
        Organization $organization,
    ): bool {
        if ($prevalidation->is_used) {
            return false;
        }

        if ($prevalidation->organization_id !== $organization->id) {
            return false;
        }

        return PrevalidationQuery::whereVisibleToIdentity(
            $organization->prevalidations(),
            $identity->address,
        )->where('id', $prevalidation->id)->exists();
    }

    /**
     * @return string
     */
    protected function getPolicyKey(): string
    {
        return 'prevalidations';
    }
}
