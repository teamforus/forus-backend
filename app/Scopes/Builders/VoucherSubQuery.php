<?php

namespace App\Scopes\Builders;

use App\Models\Voucher;
use App\Models\VoucherTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QBuilder;

class VoucherSubQuery
{
    /**
     * @param Builder|Relation $builder
     * @return Builder
     */
    public static function appendFirstUseFields(Builder|Relation $builder): Builder
    {
        $builder = $builder->addSelect([
            'vouchers.*',
            'first_transaction_date' => static::limitFirst(
                static::getFirstTransactionSubQuery()
            ),
            'first_reservation_date' => static::limitFirst(
                static::getFirstReservationOrProductVoucherSubQuery()
            ),
        ])->getQuery();

        $builder = Voucher::fromSub($builder, 'vouchers');

        $builder->selectRaw('vouchers.*, LEAST(
            Coalesce(`first_transaction_date`, `first_reservation_date`),
            Coalesce(`first_reservation_date`, `first_transaction_date`)
        ) as `first_use_date`');

        return Voucher::query()->fromSub($builder, 'vouchers');
    }

    /**
     * @param Builder|QBuilder $builder
     * @return Builder|QBuilder
     */
    private static function limitFirst(Builder|QBuilder $builder): Builder|QBuilder
    {
        return $builder->orderBy('created_at')->select('created_at')->limit(1);
    }

    /**
     * @return Builder|QBuilder
     */
    private static function getFirstTransactionSubQuery(): Builder|QBuilder
    {
        return VoucherTransactionQuery::whereOutgoing(VoucherTransaction::whereColumn([
            'voucher_transactions.voucher_id' => 'vouchers.id'
        ]));
    }

    /**
     * Product vouchers and product vouchers from reservations
     * @return Builder|QBuilder
     */
    private static function getFirstReservationOrProductVoucherSubQuery(): Builder|QBuilder
    {
        return Voucher::query()
            ->from('vouchers as product_vouchers')
            ->whereColumn('product_vouchers.parent_id', 'vouchers.id')
            ->where(fn(Builder $builder) => VoucherQuery::whereIsProductVoucher($builder));
    }
}
