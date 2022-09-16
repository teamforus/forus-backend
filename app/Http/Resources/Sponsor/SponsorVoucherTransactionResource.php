<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\VoucherTransactionNoteResource;
use App\Models\VoucherTransaction;

/**
 * @property VoucherTransaction $resource
 */
class SponsorVoucherTransactionResource extends BaseJsonResource
{
    /**
     * @var string[]
     */
    public const LOAD = [
        'voucher.fund:id,name,organization_id',
        'voucher.fund.organization.bank_connection_active.bank_connection_default_account',
        'provider:id,name,iban',
        'notes_sponsor',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $transaction = $this->resource;
        $createdAt = $transaction->created_at;
        $updatedAt = $transaction->updated_at;

        return array_merge($transaction->only([
            'id', 'organization_id', 'product_id', 'state_locale',
            'updated_at', 'address', 'state', 'payment_id', 'voucher_transaction_bulk_id',
            'transaction_cost', 'attempts', 'transfer_at', 'iban_final', 'target',
        ]), $this->getIbanFields($transaction), [
            'created_at' => $createdAt?->format('Y-m-d H:i:s'),
            'created_at_locale' => format_datetime_locale($transaction->created_at),
            'updated_at' => $updatedAt?->format('Y-m-d H:i:s'),
            'updated_at_locale' => format_datetime_locale($transaction->updated_at),
            'amount' => currency_format($transaction->amount),
            'timestamp' => $transaction->created_at->timestamp,
            'transaction_in' => $transaction->daysBeforeTransaction(),
            'organization' => $transaction->provider?->only('id', 'name'),
            'fund' => $transaction->voucher->fund->only('id', 'name', 'organization_id'),
            'notes' => VoucherTransactionNoteResource::collection($transaction->notes_sponsor),
            'target_locale' => trans('transaction.target.' . $transaction->target),
        ]);
    }

    /**
     * @param VoucherTransaction $transaction
     * @return array
     */
    public function getIbanFields(VoucherTransaction $transaction): array
    {
        return $transaction->iban_final ? $transaction->only('iban_from', 'iban_to', 'iban_to_name') : [
            'iban_from' => $transaction->voucher->fund->organization->bank_connection_active->iban ?? null,
            'iban_to' => $transaction->getTargetIban(),
            'iban_to_name' => $transaction->getTargetName(),
        ];
    }
}
