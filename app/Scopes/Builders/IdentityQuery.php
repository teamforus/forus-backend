<?php


namespace App\Scopes\Builders;

use App\Models\Fund;
use App\Models\IdentityEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class IdentityQuery
{
    /**
     * @param Relation|Builder $builder
     * @param Fund $fund
     * @param bool $withReservations
     * @return Relation|Builder
     */
    public static function appendVouchersCountFields(
        Relation|Builder $builder,
        Fund $fund,
        bool $withReservations = false
    ): Relation|Builder {
        $vouchersQuery = $fund
            ->vouchers()
            ->selectRaw('count(*)')
            ->whereColumn('vouchers.identity_address', 'identities.address');

        if (!$withReservations) {
            $vouchersQuery->whereNull('product_reservation_id');
        }

        return $builder->addSelect([
            'count_vouchers' => clone $vouchersQuery,
            'count_vouchers_active' => VoucherQuery::whereNotExpiredAndActive(clone $vouchersQuery),
            'count_vouchers_active_with_balance' => VoucherQuery::whereHasBalanceIsActiveAndNotExpired(clone $vouchersQuery),
        ]);
    }

    /**
     * @param Relation|Builder $builder
     * @return Relation|Builder
     */
    public static function appendEmailField(Relation|Builder $builder): Relation|Builder
    {
        return $builder->addSelect([
            'email' => IdentityEmail::query()
                ->whereColumn('identities.address', 'identity_emails.identity_address')
                ->where('primary', true)
                ->select('email'),
        ]);
    }
}
