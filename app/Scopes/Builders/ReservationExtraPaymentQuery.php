<?php


namespace App\Scopes\Builders;

use App\Models\Fund;
use App\Models\ReservationExtraPayment;
use App\Models\ReservationExtraPaymentRefund;
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
        return self::whereNotRefunded($query->where(function(Builder $builder) use ($organization) {
            $builder->whereHas('product_reservation.voucher.fund', function(
                Builder|Fund $builder,
            ) use ($organization) {
                $builder->whereIn('organization_id', (array) $organization);
            });
        }));
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public static function whereNotRefunded(Builder $query): Builder
    {
        $query->addSelect([
            'refund_amount' => ReservationExtraPaymentRefund::query()
                ->whereColumn('reservation_extra_payments.id', 'reservation_extra_payment_id')
                ->whereIn('state', [ReservationExtraPaymentRefund::STATE_REFUNDED])
                ->selectRaw('sum(`amount`)')
        ]);

        $query = ReservationExtraPayment::fromSub($query, 'reservation_extra_payments')->selectRaw(
            '*, CAST(IF(ISNULL(`refund_amount`), amount, amount - refund_amount) AS SIGNED) as `not_refunded`'
        );

        $query = ReservationExtraPayment::query()->fromSub($query, 'reservation_extra_payments');

        return $query->where('not_refunded', '>', 0);
    }
}