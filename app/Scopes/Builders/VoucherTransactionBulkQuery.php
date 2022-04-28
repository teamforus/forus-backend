<?php


namespace App\Scopes\Builders;

use App\Models\VoucherTransaction;
use App\Models\VoucherTransactionBulk;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class VoucherTransactionBulkQuery
 * @package App\Scopes\Builders
 */
class VoucherTransactionBulkQuery
{
    /**
     * @param Builder $builder
     * @param string|null $orderBy
     * @param string|null $orderDir
     * @return Builder
     */
    public static function order(
        Builder $builder,
        ?string $orderBy = 'created_at',
        ?string $orderDir = 'desc'
    ): Builder {
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
     * @return Builder
     */
    protected static function orderVoucherTransactionsCountQuery(): Builder
    {
        return VoucherTransaction::query()->whereColumn(
            'voucher_transaction_bulk_id', 'voucher_transaction_bulks.id'
        )->selectRaw('SUM(`voucher_transactions`.`amount`)');
    }
}
