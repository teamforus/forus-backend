<?php


namespace App\Scopes\Builders;

use App\Models\FundProvider;
use App\Models\FundProviderProduct;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class FundProviderProductQuery
 * @package App\Scopes\Builders
 */
class FundProviderProductQuery
{
    /**
     * @param Builder|SoftDeletes $builder
     * @return Builder|SoftDeletes
     */
    public static function withTrashed(Builder $builder): Builder {
        return $builder->withTrashed();
    }

    /**
     * @param Builder $builder
     * @param $identity_address
     * @return Builder
     */
    public static function whereInLimitsFilter(Builder $builder, $identity_address): Builder {
        $limit_total = \DB::raw('`fund_provider_products`.`limit_total`');
        $limit_per_identity = \DB::raw('`fund_provider_products`.`limit_per_identity`');

        $builder->whereHas('voucher_transactions', null, '<', $limit_total);

        return $builder->whereHas('voucher_transactions', static function(
            Builder $builder
        ) use ($identity_address) {
            $builder->whereHas('voucher', static function(
                Builder $builder
            ) use ($identity_address) {
                $builder->whereIn('vouchers.identity_address', (array) $identity_address);
            });
        }, '<', $limit_per_identity);
    }

    /**
     * @param Builder $query
     * @param Voucher $voucher
     * @param null $organization_id
     * @return Builder
     */
    public static function whereAvailableForVoucherFilter(
        Builder $query,
        Voucher $voucher,
        $organization_id = null
    ) {
        $query = $query->whereHas('product', static function(
            Builder $query
        ) use ($voucher, $organization_id) {
            $query->where(static function(Builder $builder) use ($voucher, $organization_id) {
                $providersQuery = FundProviderQuery::whereApprovedForFundsFilter(
                    FundProvider::query(), $voucher->fund_id,'subsidy', $voucher->product_id
                );

                if ($organization_id) {
                    $providersQuery->whereIn('organization_id', $organization_id);
                }

                $builder->whereIn('organization_id', $providersQuery->pluck('organization_id'));
            });

            return ProductQuery::approvedForFundsAndActiveFilter($query, $voucher->fund->id);
        });

        return FundProviderProductQuery::whereInLimitsFilter($query, $voucher->identity_address);
    }
}