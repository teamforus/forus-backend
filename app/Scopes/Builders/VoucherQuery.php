<?php


namespace App\Scopes\Builders;

use Illuminate\Database\Eloquent\Builder;

class VoucherQuery
{
    /**
     * @param Builder $builder
     * @param string $identity_address
     * @param $fund_id
     * @param null $organization_id Provider organization id
     * @return Builder
     */
    public static function whereProductVouchersCanBeScannedForFundBy(
        Builder $builder,
        string $identity_address,
        $fund_id,
        $organization_id = null
    ): Builder {
        return $builder->whereHas('product', static function(Builder $builder) use (
            $fund_id, $identity_address, $organization_id
        ) {
            $builder->whereHas('organization', static function(Builder $builder) use (
                $fund_id, $identity_address, $organization_id
            ) {
                if ($organization_id) {
                    $builder->whereIn('organizations.id', (array) $organization_id);
                }

                OrganizationQuery::whereHasPermissions(
                    $builder, $identity_address, 'scan_vouchers'
                );

                $builder->whereHas('fund_providers', static function(
                    Builder $builder
                ) use ($fund_id) {
                    FundProviderQuery::whereApprovedForFundsFilter(
                        $builder, $fund_id, 'product'
                    );
                });
            });

            ProductQuery::approvedForFundsFilter($builder, $fund_id);
        });
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public static function whereNotExpired(Builder $builder): Builder {
        return $builder->where(static function(Builder $builder) {
            $builder->where(
                'expire_at', '>', now()->endOfDay()
            )->whereDoesntHave('fund', static function(Builder $builder) {
                $builder->where('end_date', '<', now()->endOfDay());
            });
        });
    }
}