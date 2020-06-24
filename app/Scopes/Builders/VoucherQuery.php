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
    ) {
        return $builder->whereHas('product', function(Builder $builder) use (
            $fund_id, $identity_address, $organization_id
        ) {
            $builder->whereHas('organization', function(Builder $builder) use (
                $fund_id, $identity_address, $organization_id
            ) {
                if ($organization_id) {
                    $builder->whereIn('organizations.id', (array) $organization_id);
                }

                OrganizationQuery::whereHasPermissions(
                    $builder, $identity_address, 'scan_vouchers'
                );

                $builder->whereHas('fund_providers', function(
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
}