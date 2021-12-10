<?php

namespace App\Http\Resources\Provider;

use App\Http\Resources\MediaResource;
use App\Http\Resources\ProductReservationResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\VoucherTransactionNoteResource;
use App\Models\VoucherTransaction;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class ProviderVoucherTransactionResource
 * @property VoucherTransaction $resource
 * @package App\Http\Resources\Provider
 */
class ProviderVoucherTransactionResource extends Resource
{
    public static $load = [
        'provider',
        'provider.business_type.translations',
        'provider.logo.presets',
        'voucher.fund',
        'voucher.fund.logo.presets',
        'voucher.fund.organization.bank_connection_active.bank_connection_default_account',
        'product',
        'notes',
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

        return collect($transaction)->only([
            "id", "organization_id", "product_id", "created_at",
            "updated_at", "address", "state", "payment_id", 'iban_final',
        ])->merge($this->getIbanFields($transaction))->merge([
            'created_at_locale' => format_datetime_locale($transaction->created_at),
            'updated_at_locale' => format_datetime_locale($transaction->updated_at),
            'amount' => currency_format($transaction->amount),
            'timestamp' => $transaction->created_at->timestamp,
            'cancelable' => $transaction->isCancelable(),
            'transaction_in' => $transaction->daysBeforeTransaction(),
            "organization" => collect($transaction->provider)->only(
                "id", "name"
            )->merge([
                'logo' => new MediaResource($transaction->provider->logo),
            ]),
            "product" => new ProductResource($transaction->product),
            "fund" => collect($transaction->voucher->fund)->only([
                "id", "name", "organization_id"
            ])->merge([
                'logo' => new MediaResource($transaction->voucher->fund->logo),
            ]),
            'notes' => VoucherTransactionNoteResource::collection(
                $transaction->notes->where('group', 'provider')->values()
            ),
            'reservation' => new ProductReservationResource($transaction->product_reservation),
        ])->toArray();
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
