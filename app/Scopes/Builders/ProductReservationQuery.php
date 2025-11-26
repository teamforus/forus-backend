<?php

namespace App\Scopes\Builders;

use App\Models\ProductReservation;
use App\Models\ReservationExtraPayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ProductReservationQuery
{
    /**
     * @param Builder|Relation|ProductReservation $query
     * @param string $q
     * @return Builder|Relation|ProductReservation
     */
    public static function whereQueryFilter(
        Builder|Relation|ProductReservation $query,
        string $q = '',
        ?string $q_type = null,
    ): Builder|Relation|ProductReservation {
        return $query->where(function (Builder $builder) use ($q, $q_type) {
            $builder->where('code', 'LIKE', "%$q%");
            $builder->orWhere('first_name', 'LIKE', "%$q%");
            $builder->orWhere('last_name', 'LIKE', "%$q%");

            $builder->orWhereHas('voucher.identity.primary_email', function (Builder $builder) use ($q) {
                return $builder->where('email', 'LIKE', "%$q%");
            });

            $builder->orWhereHas('product', function (Builder $builder) use ($q) {
                ProductQuery::queryFilter($builder, $q);
            });

            $builder->orWhereHas('voucher.fund', function (Builder $builder) use ($q) {
                FundQuery::whereQueryFilter($builder, $q);
            });

            if ($q_type === 'provider') {
                $builder->orWhere('invoice_number', 'like', "%$q%");
                $builder->orWhereRelation('notes', 'description', 'like', "%$q%");
                $builder->orWhereRelation('custom_fields', 'value', 'like', "%$q%");
            }
        });
    }

    /**
     * @param Builder|Relation|ProductReservation $query
     * @param int|array|Builder $organization
     * @return Builder|Relation|ProductReservation
     */
    public static function whereProviderFilter(
        Builder|Relation|ProductReservation $query,
        mixed $organization,
    ): Builder|Relation|ProductReservation {
        return $query->where(function (Builder $builder) use ($organization) {
            $builder->whereHas('product', function (Builder $builder) use ($organization) {
                $builder->whereIn('organization_id', (array) $organization);
            });
        });
    }

    /**
     * @param Builder|Relation|ProductReservation $query
     * @param int|array|Builder $organization
     * @return Builder|Relation|ProductReservation
     */
    public static function whereSponsorFilter(
        Builder|Relation|ProductReservation $query,
        int|array|Builder $organization,
    ): Builder|Relation|ProductReservation {
        return $query->where(function (Builder $builder) use ($organization) {
            $builder->whereHas('voucher.fund', function (Builder $builder) use ($organization) {
                $builder->whereIn('organization_id', is_numeric($organization) ? [$organization] : $organization);
            });
        });
    }

    /**
     * @param Builder|Relation|ProductReservation $builder
     * @return Builder|Relation|ProductReservation
     */
    public static function whereNotExpired(
        Builder|Relation|ProductReservation $builder,
    ): Builder|Relation|ProductReservation {
        return $builder->whereNotIn('id', self::whereExpired(
            ProductReservation::query(),
        )->select('id'));
    }

    /**
     * @param Builder|Relation|ProductReservation $builder
     * @return Builder|Relation|ProductReservation
     */
    public static function whereExpired(
        Builder|Relation|ProductReservation $builder,
    ): Builder|Relation|ProductReservation {
        $now = now()->format('Y-m-d');

        return $builder->whereHas('voucher', function (Builder $builder) use ($now) {
            $builder->where('expire_at', '<', function ($query) use ($now) {
                $query->selectRaw('DATE_SUB(?, INTERVAL `reservation_approve_offset` DAY)', [$now])
                    ->from('fund_configs')
                    ->whereColumn('fund_configs.fund_id', 'vouchers.fund_id')
                    ->limit(1);
            });

            $builder->orWhereHas('fund', function (Builder $builder) use ($now) {
                $builder->where('end_date', '<', function ($query) use ($now) {
                    $query->selectRaw('DATE_SUB(?, INTERVAL `reservation_approve_offset` DAY)', [$now])
                        ->from('fund_configs')
                        ->whereColumn('fund_configs.fund_id', 'funds.id')
                        ->limit(1);
                });
            });
        });
    }

    /**
     * @param Builder|Relation|ProductReservation $builder
     * @return Builder|Relation|ProductReservation
     */
    public static function whereExpiredAndPending(
        Builder|Relation|ProductReservation $builder,
    ): Builder|Relation|ProductReservation {
        return self::whereExpired($builder)->where('state', ProductReservation::STATE_PENDING);
    }

    /**
     * @param Builder|Relation|ProductReservation $builder
     * @return Builder|Relation|ProductReservation
     */
    public static function whereArchived(
        Builder|Relation|ProductReservation $builder,
    ): Builder|Relation|ProductReservation {
        return $builder->where(function (Builder $builder) {
            $builder->whereIn('state', ProductReservation::STATES_CANCELED);
            $builder->orWhere(fn (Builder $builder) => self::whereExpiredAndPending($builder));
        });
    }

    /**
     * @param Builder|Relation|ProductReservation $builder
     * @return Builder|Relation|ProductReservation
     */
    public static function whereNotArchived(
        Builder|Relation|ProductReservation $builder
    ): Builder|Relation|ProductReservation {
        return $builder->whereNotIn('id', self::whereArchived(
            ProductReservation::query()->select('id')
        ));
    }

    /**
     * @param Builder|Relation|ProductReservation $builder
     * @param int $waitingTime
     * @return Builder|Relation|ProductReservation
     */
    public static function whereExtraPaymentExpired(
        Builder|Relation|ProductReservation $builder,
        int $waitingTime,
    ): Builder|Relation|ProductReservation {
        $expiredAt = now()->subMinutes($waitingTime);

        return $builder->where(function (Builder $query) use ($expiredAt) {
            $query->where('state', '=', ProductReservation::STATE_WAITING);
            $query->where('amount_extra', '>', 0);

            $query->where(function (Builder $query) use ($expiredAt) {
                $query->where('created_at', '<', $expiredAt);

                $query->orWhereHas('extra_payment', function (Builder $query) {
                    $query->where('expires_at', '<', now());
                    $query->where('state', '!=', ReservationExtraPayment::STATE_PAID);
                });
            });
        });
    }
}
