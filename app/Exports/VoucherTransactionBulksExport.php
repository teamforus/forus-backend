<?php

namespace App\Exports;

use App\Exports\Base\BaseExport;
use App\Models\VoucherTransactionBulk;
use Illuminate\Database\Eloquent\Model;

class VoucherTransactionBulksExport extends BaseExport
{
    protected static string $transKey = 'voucher_transaction_bulks';

    /**
     * @var array|string[]
     */
    protected static array $exportFields = [
        'id',
        'quantity',
        'amount',
        'bank_name',
        'date_transaction',
        'state',
    ];

    /**
     * @var array|string[]
     */
    protected array $builderWithArray = [
        'voucher_transactions',
        'bank_connection.bank',
    ];

    /**
     * @var array|string[]
     */
    protected array $builderWithCountArray = [
        'voucher_transactions',
    ];

    /**
     * @param Model|VoucherTransactionBulk $model
     * @return array
     */
    protected function getRow(Model|VoucherTransactionBulk $model): array
    {
        return [
            'id' => $model->id,
            'quantity' => $model->voucher_transactions_count,
            'amount' => currency_format($model->voucher_transactions->sum('amount')),
            'bank_name' => $model->bank_connection->bank->name,
            'date_transaction' => format_datetime_locale($model->created_at),
            'state' => trans("export.voucher_transaction_bulks.state-values.$model->state"),
        ];
    }
}
