<?php

namespace App\Http\Resources;

use App\Http\Resources\Sponsor\SponsorVoucherTransactionResource;
use App\Models\VoucherTransactionBulk;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read VoucherTransactionBulk $resource
 */
class VoucherTransactionBulkResource extends JsonResource
{
    /**
     * @return array
     */
    static function load(): array
    {
        return [
            'voucher_transactions.voucher.fund:id,name,organization_id',
            'voucher_transactions.provider:id,name',
            'voucher_transactions.notes_sponsor',
        ];
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $transactionBulk = $this->resource;

        return array_merge($transactionBulk->only('id', 'state', 'state_locale', 'payment_id'), [
            'transactions' => SponsorVoucherTransactionResource::collection($transactionBulk->voucher_transactions),
            'voucher_transactions_amount' => $transactionBulk->voucher_transactions->sum('amount'),
            'voucher_transactions_count' => $transactionBulk->voucher_transactions->count(),
            'voucher_transactions_cost' => $transactionBulk->voucher_transactions->sum('transaction_cost'),
            'created_at' => $transactionBulk->created_at ? $transactionBulk->created_at->format('Y-m-d H:i:s') : null,
            'created_at_locale' => format_datetime_locale($transactionBulk->created_at),
        ]);
    }
}
