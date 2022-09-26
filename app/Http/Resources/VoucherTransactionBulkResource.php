<?php

namespace App\Http\Resources;

use App\Models\VoucherTransactionBulk;
use App\Services\BankService\Resources\BankResource;

/**
 * @property-read VoucherTransactionBulk $resource
 */
class VoucherTransactionBulkResource extends BaseJsonResource
{
    public const LOAD = [
        'voucher_transactions',
        'bank_connection.bank',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $transactionBulk = $this->resource;
        $executionDate = $this->resource->execution_date;

        return array_merge($transactionBulk->only('id', 'state', 'state_locale', 'payment_id'), [
            'auth_url' => $this->getAuthUrl($transactionBulk),
            'bank' => new BankResource($transactionBulk->bank_connection->bank),
            'execution_date' => $executionDate?->format('Y-m-d'),
            'execution_date_locale' => format_date_locale($transactionBulk->execution_date),
            'voucher_transactions_amount' => $transactionBulk->voucher_transactions->sum('amount'),
            'voucher_transactions_count' => $transactionBulk->voucher_transactions->count(),
            'voucher_transactions_cost' => $transactionBulk->voucher_transactions->sum('transaction_cost'),
        ], $this->timestamps($this->resource, 'created_at'));
    }

    /**
     * @param VoucherTransactionBulk $bulk
     * @return string|null
     */
    private function getAuthUrl(VoucherTransactionBulk $bulk): ?string
    {
        $bank = $bulk->bank_connection->bank;

        if ($bank->isBNG() && $bulk->isPending() && $bulk->execution_date->isFuture()) {
            return $bulk->auth_url;
        }

        return null;
    }
}
