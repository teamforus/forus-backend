<?php


namespace App\Scopes\Builders;

use App\Models\FundProvider;
use App\Models\Product;
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
     * @param null|int|array|Builder $organization_id
     * @param bool $validateLimits
     * @return Builder
     */
    public static function whereAvailableForSubsidyVoucher(
        Builder $query,
        Voucher $voucher,
        $organization_id = null,
        $validateLimits = true
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

        return $validateLimits ? self::whereInSubsidyLimitsFilter($query, $voucher) : $query;
    }

    /**
     * @param Builder $builder
     * @param Voucher $voucher
     * @return Builder
     */
    public static function whereInSubsidyLimitsFilter(Builder $builder, Voucher $voucher): Builder
    {
        return $builder->whereHas('product', function(Builder $builder) use ($voucher) {
            $query = ProductSubQuery::appendReservationStats([
                'voucher_id' => $voucher->id,
            ], Product::query())->where('limit_available', '>', 0);

            $builder->whereIn('id', $query->select('id'));
        });
    }
}