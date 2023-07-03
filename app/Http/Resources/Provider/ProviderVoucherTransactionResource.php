<?php

namespace App\Http\Resources\Provider;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\ProductReservationResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\Tiny\FundTinyResource;
use App\Http\Resources\Tiny\OrganizationTinyResource;
use App\Http\Resources\VoucherTransactionNoteResource;
use App\Models\VoucherTransaction;

/**
 * Class ProviderVoucherTransactionResource
 * @property VoucherTransaction $resource
 * @package App\Http\Resources\Provider
 */
class ProviderVoucherTransactionResource extends BaseJsonResource
{
    public const LOAD = [
        'provider',
        'provider.business_type.translations',
        'provider.logo.presets',
        'voucher.fund',
        'voucher.fund.logo.presets',
        'voucher.fund.organization.bank_connection_active.bank_connection_default_account',
        'product',
        'notes',
        'notes_provider',
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
        $providerNotes = $transaction->notes_provider->values();

        return array_merge($transaction->only([
            "id", "organization_id", "product_id", "address",
            "state", 'state_locale', "payment_id", 'iban_final',
        ]), $this->getIbanFields($transaction), [
            'amount' => currency_format($transaction->amount),
            'timestamp' => $transaction->created_at->timestamp,
            'cancelable' => $transaction->isCancelable(),
            'transaction_in' => $transaction->daysBeforeTransaction(),
            "fund" => new FundTinyResource($transaction->voucher->fund),
            'notes' => VoucherTransactionNoteResource::collection($providerNotes),
            "product" => new ProductResource($transaction->product),
            'reservation' => new ProductReservationResource($transaction->product_reservation),
            "organization" => new OrganizationTinyResource($transaction->provider),
        ], $this->timestamps($transaction, 'created_at', 'updated_at'));
    }

    /**
     * @param VoucherTransaction $transaction
     * @return array
     */
    public function getIbanFields(VoucherTransaction $transaction): array
    {
        return $transaction->iban_final ? $transaction->only('iban_from', 'iban_to') : [
            'iban_from' => $transaction->voucher->fund->organization->bank_connection_active->iban ?? null,
            'iban_to' => $transaction->provider->iban ?? null,
        ];
    }
}
