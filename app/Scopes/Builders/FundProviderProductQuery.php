<?php


namespace App\Scopes\Builders;

use App\Models\FundProvider;
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
     * @param Builder $query
     * @param Voucher $voucher
     * @param array|int|null $organization_id
     * @return Builder
     */
    public static function whereAvailableForVoucherFilter(
        Builder $query,
        Voucher $voucher,
        $organization_id = null
    ): Builder {
        $query->whereHas('product', static function(
            Builder $query
        ) use ($voucher, $organization_id) {
            $query->where(static function(Builder $builder) use ($voucher, $organization_id) {
                $providersQuery = FundProviderQuery::whereApprovedForFundsFilter(
                    FundProvider::query(), $voucher->fund_id,'subsidy', $voucher->product_id
                );

                if ($organization_id) {
                    $providersQuery->whereIn('organization_id', (array) $organization_id);
                }

                $builder->whereIn('organization_id', $providersQuery->pluck('organization_id'));
            });

            return ProductQuery::approvedForFundsAndActiveFilter($query, $voucher->fund->id);
        });

        $query->whereHas('fund_provider', static function(Builder $builder) use ($voucher) {
            $builder->where('fund_id', '=', $voucher->fund_id);
        });

        return self::whereInLimitsFilter($query, $voucher);
    }

    /**
     * @param Builder $builder
     * @param Voucher $voucher
     * @return Builder
     */
    public static function whereInLimitsFilter(
        Builder $builder,
        Voucher $voucher
    ): Builder {
        $limit_per_identity = \DB::raw(sprintf(
            "(`fund_provider_products`.`limit_per_identity` * %s)",
            $voucher->limit_multiplier
        ));

        $builder->where(static function (Builder $builder) use ($voucher) {
            $limit_total = \DB::raw('`fund_provider_products`.`limit_total`');

            $builder->whereHas('product.voucher_transactions', static function(Builder $builder) use ($voucher) {
                // nesting is required, do not replace with 'product.voucher_transactions.voucher'
                $builder->whereHas('voucher', static function(Builder $builder) use ($voucher) {
                    $builder->where('vouchers.fund_id', '=', $voucher->fund_id);
                });
            },'<', $limit_total);

            $builder->orWhere('limit_total_unlimited', '=', true);
        });

        $builder->whereHas('product.voucher_transactions', static function(Builder $builder) use ($voucher) {
            // nesting is required, do not replace with 'product.voucher_transactions.voucher'
            return $builder->whereHas('voucher', static function(Builder $builder) use ($voucher) {
                $builder->where('vouchers.id', '=', $voucher->id);
            });
        }, '<', $limit_per_identity);

        return $builder;
    }
}