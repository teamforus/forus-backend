<?php

namespace App\Exports\Base;

use App\Models\VoucherTransaction;
use Illuminate\Database\Eloquent\Model;

class BaseVoucherTransactionsExport extends BaseExport
{
    protected static string $transKey = 'voucher_transactions';

    /**
     * @var array|string[]
     */
    protected array $builderWithArray = [
        'product',
        'provider',
        'voucher.fund',
        'notes_provider',
        'product_reservation',
    ];

    /**
     * @param Model|VoucherTransaction $model
     * @return array
     */
    protected function getRow(Model|VoucherTransaction $model): array
    {
        return [
            'id' => $model->id,
            'amount' => currency_format($model->amount),
            'amount_extra' => $model->product_reservation?->amount_extra > 0 ?
                currency_format($model->product_reservation?->amount_extra)
                : '',
            'amount_extra_cash' => currency_format($model->amount_extra_cash),
            'method' => $model->product_reservation?->amount_extra > 0
                ? 'iDeal + Tegoed'
                : 'Tegoed',
            'branch_id' => $model->branch_id,
            'branch_name' => $model->branch_name,
            'branch_number' => $model->branch_number,
            'date_transaction' => format_datetime_locale($model->created_at),
            'date_payment' => format_datetime_locale($model->payment_time),
            'fund_name' => $model->voucher->fund->name,
            'product_id' => $model->product?->id,
            'product_name' => $model->product?->name,
            'provider' => $model->targetIsProvider() ? $model->provider->name : '',
            'date_non_cancelable' => format_date_locale($model->non_cancelable_at),
            'state' => trans("export.voucher_transactions.state-values.$model->state"),
            'bulk_status_locale' => $model->bulk_status_locale,
            'notes_provider' => $model->notes_provider->pluck('message')->implode("\n"),
            'reservation_code' => $model->product_reservation?->code,
        ];
    }
}
