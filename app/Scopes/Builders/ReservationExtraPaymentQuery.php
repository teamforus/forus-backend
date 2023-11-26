<?php


namespace App\Scopes\Builders;

use App\Models\Fund;
use Illuminate\Database\Eloquent\Builder;

class ReservationExtraPaymentQuery
{
    /**
     * @param Builder $query
     * @param string $q
     * @return Builder
     */
    public static function whereQueryFilter(Builder $query, string $q): Builder
    {
        return $query->where(function(Builder $builder) use ($q) {
            $builder->whereHas('product_reservation.product', function(Builder $builder) use ($q) {
                ProductQuery::queryFilter($builder, $q);
            });

            $builder->orWhereHas('product_reservation.voucher.fund', function(Builder $builder) use ($q) {
                FundQuery::whereQueryFilter($builder, $q);
            });
        });
    }

    /**
     * @param Builder $query
     * @param array|int $organization
     * @return Builder
     */
    public static function whereSponsorFilter(Builder $query, array|int $organization): Builder
    {
        return $query->where(function(Builder $builder) use ($organization) {
            $builder->whereHas('product_reservation.voucher.fund', function(
                Builder|Fund $builder,
            ) use ($organization) {
                $builder->whereIn('organization_id', (array) $organization);
            });
        });
    }
}