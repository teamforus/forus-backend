<?php

namespace App\Scopes\Builders;

use App\Models\ReservationExtraPayment;
use App\Models\ReservationExtraPaymentRefund;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ReservationExtraPaymentQuery
{
    /**
     * @param Builder|Relation|ReservationExtraPayment $query
     * @param string $q
     * @return Builder|Relation|ReservationExtraPayment
     */
    public static function whereQueryFilter(
        Builder|Relation|ReservationExtraPayment $query,
        string $q,
    ): Builder|Relation|ReservationExtraPayment {
        return $query->where(function (Builder $builder) use ($q) {
            $builder->whereHas('product_reservation.product', function (Builder $builder) use ($q) {
                ProductQuery::queryFilter($builder, $q);
            });

            $builder->orWhereHas('product_reservation.voucher.fund', function (Builder $builder) use ($q) {
                FundQuery::whereQueryFilter($builder, $q);
            });
        });
    }

    /**
     * @param Builder|Relation|ReservationExtraPayment $query
     * @param array|int $organization
     * @return Builder|Relation|ReservationExtraPayment
     */
    public static function whereSponsorFilter(
        Builder|Relation|ReservationExtraPayment $query,
        array|int $organization,
    ): Builder|Relation|ReservationExtraPayment {
        return self::whereNotRefunded($query->where(function (Builder $builder) use ($organization) {
            $builder->whereHas('product_reservation.voucher.fund', function (Builder $builder) use ($organization) {
                $builder->whereIn('organization_id', (array) $organization);
            });
        }));
    }

    /**
     * @param Builder|Relation|ReservationExtraPayment $query
     * @return Builder|Relation|ReservationExtraPayment
     */
    public static function whereNotRefunded(
        Builder|Relation|ReservationExtraPayment $query,
    ): Builder|Relation|ReservationExtraPayment {
        $query->addSelect([
            '__refund_amount' => ReservationExtraPaymentRefund::query()
                ->whereColumn('reservation_extra_payments.id', 'reservation_extra_payment_id')
                ->whereIn('state', [ReservationExtraPaymentRefund::STATE_REFUNDED])
                ->selectRaw('sum(`amount`)'),
        ]);

        $query = ReservationExtraPayment::fromSub($query, 'reservation_extra_payments')->selectRaw(
            '*, CAST(IF(ISNULL(`__refund_amount`), amount, amount - __refund_amount) AS SIGNED) as `__not_refunded_amount`'
        );

        $query = ReservationExtraPayment::query()
            ->fromSub($query, 'reservation_extra_payments')
            ->selectRaw('*');

        return $query->where('__not_refunded_amount', '>', 0);
    }
}
