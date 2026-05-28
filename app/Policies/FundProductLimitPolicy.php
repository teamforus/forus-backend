<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\FundProductLimit;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use App\Scopes\Builders\FundQuery;
use Illuminate\Database\Eloquent\Builder;

class FundProductLimitPolicy extends BasePolicy
{
    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function viewAny(Identity $identity, Organization $organization): bool
    {
        return $this->viewAsSponsor($identity, $organization);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function create(Identity $identity, Organization $organization): bool
    {
        return $this->viewAsSponsor($identity, $organization);
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
            $this->viewAsSponsor($identity, $organization) &&
            in_array($fundProductLimit->fund_id, $this->getAvailableFundIds($organization));
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
            $this->viewAsSponsor($identity, $organization) &&
            in_array($fundProductLimit->fund_id, $this->getAvailableFundIds($organization));
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    protected function viewAsSponsor(Identity $identity, Organization $organization): bool
    {
        return
            $organization->allow_fund_product_limits &&
            $organization->identityCan($identity, Permission::MANAGE_PROVIDERS);
    }

    /**
     * @param Organization $organization
     * @return array
     */
    protected function getAvailableFundIds(Organization $organization): array
    {
        return $organization->funds()
            ->where(function (Builder $builder) {
                FundQuery::whereIsInternal($builder);
                FundQuery::whereIsConfiguredByForus($builder);
            })
            ->where('state', '!=', Fund::STATE_CLOSED)
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
