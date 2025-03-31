<?php

namespace App\Exports;

use App\Models\Organization;
use App\Models\VoucherTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

abstract class BaseVoucherTransactionsExport extends BaseFieldedExport
{
    protected static string $transKey = 'voucher_transactions';

    /**
     * @param Request $request
     * @param Organization $organization
     * @param array $fields
     */
    public function __construct(Request $request, Organization $organization, protected array $fields = [])
    {
        $this->data = $this->export($request, $organization);
    }

    /**
     * @param Request $request
     * @param Organization $organization
     * @return \Illuminate\Support\Collection
     */
    abstract protected function export(Request $request, Organization $organization): Collection;

    /**
     * @param Collection $data
     * @return Collection
     */
    protected function exportTransform(Collection $data): Collection
    {
        return $this->transformKeys($data->map(fn (VoucherTransaction $transaction) => array_only(
            $this->getRow($transaction), $this->fields
        )));
    }

    /**
     * @param VoucherTransaction $transaction
     * @return array
     */
    protected function getRow(VoucherTransaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'amount' => currency_format($transaction->amount),
            'amount_extra' => $transaction->product_reservation?->amount_extra > 0 ?
                currency_format($transaction->product_reservation?->amount_extra)
                : '',
            'amount_extra_cash' => currency_format($transaction->amount_extra_cash),
            'method' => $transaction->product_reservation?->amount_extra > 0
                ? 'iDeal + Tegoed'
                : 'Tegoed',
            'branch_id' => $transaction->branch_id,
            'branch_name' => $transaction->branch_name,
            'branch_number' => $transaction->branch_number,
            'date_transaction' => format_datetime_locale($transaction->created_at),
            'date_payment' => format_datetime_locale($transaction->payment_time),
            'fund_name' => $transaction->voucher->fund->name,
            'product_id' => $transaction->product?->id,
            'product_name' => $transaction->product?->name,
            'provider' => $transaction->targetIsProvider() ? $transaction->provider->name : '',
            'date_non_cancelable' => format_date_locale($transaction->non_cancelable_at),
            'state' => trans("export.voucher_transactions.state-values.$transaction->state"),
            'bulk_status_locale' => $transaction->bulk_status_locale,
        ];
    }
}
