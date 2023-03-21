<?php


namespace App\Scopes\Builders;

use App\Models\FundProvider;
use App\Models\FundProviderProduct;
use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class FundProviderProductQuery
{
    /**
     * @param Builder|FundProvider $builder
     * @return Builder|FundProvider
     */
    public static function withTrashed(Builder|FundProvider $builder): Builder|FundProvider
    {
        return $builder->withTrashed();
    }

    /**
     * @param Builder|FundProvider $query
     * @param Voucher $voucher
     * @param null|int|array|Builder $organization_id
     * @param bool $validateLimits
     * @return Builder
     */
    public static function whereAvailableForSubsidyVoucher(
        Builder|FundProvider $query,
        Voucher $voucher,
        null|int|array|Builder $organization_id = null,
        bool $validateLimits = true
    ): Builder {
        $query->whereHas('product', static function(Builder $query) use ($voucher, $organization_id) {
            $query->where(static function(Builder $builder) use ($voucher, $organization_id) {
                $providersQuery = FundProviderQuery::whereApprovedForFundsFilter(
                    FundProvider::query(), $voucher->fund_id,'subsidy', $voucher->product_id
                );

                if (is_numeric($organization_id) || is_array($organization_id)) {
                    $providersQuery->whereIn('organization_id', (array) $organization_id);
                }

                if ($organization_id instanceof Builder) {
                    $providersQuery->whereIn('organization_id', $organization_id);
                }

                $builder->whereIn('organization_id', $providersQuery->pluck('organization_id'));
            });

            return ProductQuery::approvedForFundsAndActiveFilter($query, $voucher->fund->id);
        });

        $query->whereHas('fund_provider', static function(Builder $builder) use ($voucher) {
            $builder->where('fund_id', '=', $voucher->fund_id);
        });

        if ($voucher->product_id) {
            $query->where('product_id', $voucher->product_id);
        }

        return $validateLimits ? self::whereInLimitsFilter($query, $voucher) : $query;
    }

    /**
     * @param Builder $builder
     * @param Voucher $voucher
     * @return Builder
     */
    public static function whereInLimitsFilter(Builder $builder, Voucher $voucher): Builder
    {
        return $builder->whereHas('product', function(Builder $builder) use ($voucher) {
            $query = ProductSubQuery::appendReservationStats([
                'voucher_id' => $voucher->id,
            ], (clone $builder));

            $query->where(function(Builder $builder) use ($voucher) {
                $builder->where('limit_available', '>', 0);

                if ($voucher->fund->isTypeBudget()) {
                    $builder->orWhereNull('limit_available');
                }
            });

            $builder->whereIn('id', $query->select('id'));
        });
    }

    /**
     * @param Builder|Relation|FundProviderProduct $builder
     * @return Builder|Relation|FundProviderProduct
     */
    public static function whereConfigured(
        Builder|Relation|FundProviderProduct $builder
    ): Builder|Relation|FundProviderProduct {
        return $builder->where(function(Builder|FundProviderProduct $builder) {
            $builder->whereNotNull('expire_at');
            $builder->whereNotNull('limit_total');
            $builder->orWhereNotNull('limit_per_identity');
            $builder->orWhere('limit_total_unlimited', true);
        });
    }
}