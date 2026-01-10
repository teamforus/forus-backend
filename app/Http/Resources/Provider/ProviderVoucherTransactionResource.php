<?php

namespace App\Http\Resources\Provider;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\ProductReservationResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\Tiny\FundTinyResource;
use App\Http\Resources\Tiny\OrganizationTinyResource;
use App\Http\Resources\VoucherTransactionNoteResource;
use App\Models\VoucherTransaction;
use Illuminate\Http\Request;

/**
 * @property VoucherTransaction $resource
 */
class ProviderVoucherTransactionResource extends BaseJsonResource
{
    public const array LOAD = [
        'provider',
        'voucher.fund',
        'voucher.fund.organization.bank_connection_active.bank_connection_default_account',
    ];

    public const array LOAD_NESTED = [
        'provider' => OrganizationTinyResource::class,
        'voucher.fund' => FundTinyResource::class,
        'product' => ProductResource::class,
        'product_reservation' => ProductReservationResource::class,
        'notes_provider' => VoucherTransactionNoteResource::class,
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $transaction = $this->resource;

        return array_merge($transaction->only([
            'id', 'organization_id', 'product_id', 'address',
            'state', 'state_locale', 'payment_id', 'iban_final',
            'branch_name', 'branch_number', 'branch_id',
        ]), $this->getIbanFields($transaction), [
            'amount' => currency_format($transaction->amount),
            'amount_locale' => currency_format_locale($transaction->amount),
            'amount_extra_cash' => currency_format($transaction->amount_extra_cash),
            'amount_extra_cash_locale' => currency_format_locale($transaction->amount_extra_cash),
            'timestamp' => $transaction->created_at->timestamp,
            'cancelable' => $transaction->isCancelable(),
            'transfer_in' => $transaction->daysBeforeTransaction(),
            'fund' => new FundTinyResource($transaction->voucher->fund),
            'notes' => VoucherTransactionNoteResource::collection($transaction->notes_provider),
            'product' => new ProductResource($transaction->product),
            'reservation' => new ProductReservationResource($transaction->product_reservation),
            'organization' => new OrganizationTinyResource($transaction->provider),
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
