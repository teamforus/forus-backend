<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\Tiny\ProductTinyResource;
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
        'product.photo.presets',
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

        return array_merge($transaction->only([
            'id', 'organization_id', 'product_id', 'state_locale', 'updated_at', 'address', 'state',
            'payment_id', 'voucher_transaction_bulk_id', 'transaction_cost', 'attempts',
            'transfer_at', 'iban_final', 'target', 'target_locale', 'uid',
        ]), $this->getIbanFields($transaction), [
            'amount' => currency_format($transaction->amount),
            'timestamp' => $transaction->created_at->timestamp,
            'transaction_in' => $transaction->daysBeforeTransaction(),
            'organization' => $transaction->provider?->only('id', 'name'),
            'fund' => $transaction->voucher->fund->only('id', 'name', 'organization_id'),
            'notes' => VoucherTransactionNoteResource::collection($transaction->notes_sponsor),
            'bulk_status_locale' => $transaction->bulk_status_locale,
            'product' => new ProductTinyResource($transaction->product),
            'voucher' => $transaction->voucher->only('id', 'identity_address', 'fund_id', 'amount', 'state'),
        ], $this->timestamps($transaction, 'created_at', 'updated_at'));
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
