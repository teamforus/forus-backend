<?php

namespace App\Policies;

use App\Models\FundProductLimit;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use App\Scopes\Builders\FundQuery;

class FundProductLimitPolicy extends BasePolicy
{
    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function viewAny(Identity $identity, Organization $organization): bool
    {
        return $this->canManageFundProductLimits($identity, $organization);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function create(Identity $identity, Organization $organization): bool
    {
        return $this->canManageFundProductLimits($identity, $organization);
    }

    /**
     * @param Identity $identity
     * @param FundProductLimit $fundProductLimit
     * @param Organization $organization
     * @return bool
     */
    public function show(Identity $identity, FundProductLimit $fundProductLimit, Organization $organization): bool
    {
        return
            $this->canManageFundProductLimits($identity, $organization) &&
            $this->ownsAvailableFundProductLimit($fundProductLimit, $organization);
    }

    /**
     * @param Identity $identity
     * @param FundProductLimit $fundProductLimit
     * @param Organization $organization
     * @return bool
     */
    public function update(Identity $identity, FundProductLimit $fundProductLimit, Organization $organization): bool
    {
        return
            $this->canManageFundProductLimits($identity, $organization) &&
            $this->ownsAvailableFundProductLimit($fundProductLimit, $organization);
    }

    /**
     * @param Identity $identity
     * @param FundProductLimit $fundProductLimit
     * @param Organization $organization
     * @return bool
     */
    public function destroy(Identity $identity, FundProductLimit $fundProductLimit, Organization $organization): bool
    {
        return
            $this->canManageFundProductLimits($identity, $organization) &&
            $this->ownsAvailableFundProductLimit($fundProductLimit, $organization);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    protected function canManageFundProductLimits(Identity $identity, Organization $organization): bool
    {
        return
            $organization->allow_fund_product_limits &&
            $organization->identityCan($identity, Permission::MANAGE_PROVIDERS);
    }

    /**
     * @param FundProductLimit $fundProductLimit
     * @param Organization $organization
     * @return bool
     */
    protected function ownsAvailableFundProductLimit(
        FundProductLimit $fundProductLimit,
        Organization $organization,
    ): bool {
        return in_array($fundProductLimit->fund_id, $this->getAvailableFundIds($organization));
    }

    /**
     * @param Organization $organization
     * @return array
     */
    protected function getAvailableFundIds(Organization $organization): array
    {
        return FundQuery::whereIsInternalConfiguredAndNotClosed($organization->funds())
            ->pluck('id')
            ->all();
    }

    /**
     * @return string
     */
    protected function getPolicyKey(): string
    {
        return 'fund_product_limits';
    }
}
