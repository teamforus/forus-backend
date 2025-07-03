<?php

namespace App\Scopes\Builders;

use App\Models\FundProviderProduct;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class FundProviderProductQuery
{
    /**
     * @param Builder|Relation|FundProviderProduct $builder
     * @return Builder|Relation|FundProviderProduct
     */
    public static function withTrashed(
        Builder|Relation|FundProviderProduct $builder
    ): Builder|Relation|FundProviderProduct {
        return $builder->withTrashed();
    }

    /**
     * @param Builder|Relation|FundProviderProduct $query
     * @param Voucher $voucher
     * @param null|int|array|Builder $organizationId
     * @param bool $validateLimits
     * @return Builder|Relation|FundProviderProduct
     */
    public static function whereAvailableForVoucher(
        Builder|Relation|FundProviderProduct $query,
        Voucher $voucher,
        null|int|array|Builder $organizationId = null,
        bool $validateLimits = true,
    ): Builder|Relation|FundProviderProduct {
        $query->where(function (Builder $builder) use ($voucher, $organizationId, $validateLimits) {
            $builder->whereHas('fund_provider', function (Builder $builder) use ($voucher, $organizationId) {
                $builder->where('fund_id', $voucher->fund_id);

                if ($organizationId) {
                    if (is_numeric($organizationId) || is_array($organizationId)) {
                        $builder->whereIn('organization_id', (array) $organizationId);
                    }

                    if ($organizationId instanceof Builder) {
                        $builder->whereIn('organization_id', $organizationId);
                    }
                }
            });

            $builder->where(function (Builder $builder) use ($voucher) {
                $builder->where(function (Builder $builder) use ($voucher) {
                    $builder->where('payment_type', FundProviderProduct::PAYMENT_TYPE_BUDGET);
                    $builder->whereRelation('product', 'price', '<=', $voucher->amount_available);
                });

                $builder->orWhere(function (Builder $builder) use ($voucher) {
                    $builder->where('payment_type', FundProviderProduct::PAYMENT_TYPE_SUBSIDY);
                    $builder->where('price', '<=', $voucher->amount_available);
                });
            });
        });

        return $validateLimits ? self::whereInLimitsFilter($query, $voucher) : $query;
    }

    /**
     * @param Builder|Relation|FundProviderProduct $builder
     * @param Voucher $voucher
     * @return Builder|Relation|FundProviderProduct
     */
    public static function whereInLimitsFilter(
        Builder|Relation|FundProviderProduct $builder,
        Voucher $voucher,
    ): Builder|Relation|FundProviderProduct {
        return $builder->whereHas('product', function (Builder $builder) use ($voucher) {
            $query = ProductSubQuery::appendReservationStats([
                'voucher_id' => $voucher->id,
            ], (clone $builder));

            $query->where(function (Builder $builder) use ($voucher) {
                $builder->where('limit_available', '>', 0);
                $builder->orWhereNull('limit_available');
            });

            $builder->whereIn('id', $query->select('id'));
        });
    }
}
