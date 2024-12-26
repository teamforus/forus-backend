<?php


namespace App\Scopes\Builders;

use App\Models\VoucherTransaction;
use App\Models\VoucherTransactionBulk;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class VoucherTransactionBulkQuery
{
    /**
     * @param Builder|Relation|VoucherTransactionBulk $builder
     * @param string|null $orderBy
     * @param string|null $orderDir
     * @return Builder|Relation|VoucherTransactionBulk
     */
    public static function order(
        Builder|Relation|VoucherTransactionBulk $builder,
        ?string $orderBy = 'created_at',
        ?string $orderDir = 'desc'
    ): Builder|Relation|VoucherTransactionBulk {
        $fields = VoucherTransactionBulk::SORT_BY_FIELDS;

        $builder->addSelect([
            'amount' => static::orderVoucherTransactionsCountQuery(),
        ]);

        $builder->withCount('voucher_transactions');

        return $builder->orderBy(
            $orderBy && in_array($orderBy, $fields) ? $orderBy : 'created_at',
            $orderDir ?: 'desc'
        );
    }

    /**
     * @return Builder|Relation|VoucherTransactionBulk
     */
    protected static function orderVoucherTransactionsCountQuery(): Builder|Relation|VoucherTransactionBulk
    {
        return VoucherTransaction::query()->whereColumn(
            'voucher_transaction_bulk_id', 'voucher_transaction_bulks.id'
        )->selectRaw('SUM(`voucher_transactions`.`amount`)');
    }
}
