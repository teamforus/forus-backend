<?php


namespace App\Scopes\Builders;

use App\Models\ProductReservation;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class ProductQuery
 * @package App\Scopes\Builders
 */
class ProductReservationQuery
{
    /**
     * @param Builder $query
     * @param string $q
     * @return Builder
     */
    public static function whereQueryFilter(Builder $query, string $q = ''): Builder
    {
        return $query->where(function(Builder $builder) use ($q) {
            $builder->where('code', 'LIKE', "%$q%");
            $builder->orWhere('first_name', 'LIKE', "%$q%");
            $builder->orWhere('last_name', 'LIKE', "%$q%");

            $builder->orWhereHas('product', function(Builder $builder) use ($q) {
                ProductQuery::queryFilter($builder, $q);
            });

            $builder->orWhereHas('voucher.fund', function(Builder $builder) use ($q) {
                FundQuery::whereQueryFilter($builder, $q);
            });
        });
    }

    /**
     * @param Builder $query
     * @param int|array|Builder $organization
     * @return Builder
     */
    public static function whereProviderFilter(Builder $query, $organization): Builder
    {
        return $query->where(function(Builder $builder) use ($organization) {
            $builder->whereHas('product', function(Builder $builder) use ($organization) {
                $builder->whereIn('products.organization_id', (array) $organization);
            });
        });
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public static function whereNotExpired(Builder $builder): Builder
    {
        return $builder->where(static function(Builder $builder) {
            $builder->where('product_reservations.expire_at', '>=', today());
        });
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public static function whereNotExpiredAndAccepted(Builder $builder): Builder
    {
        return self::whereNotExpired($builder)->where('state', ProductReservation::STATE_ACCEPTED);
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public static function whereNotExpiredAndPending(Builder $builder): Builder
    {
        return self::whereNotExpired($builder)->where('state', ProductReservation::STATE_PENDING);
    }
}