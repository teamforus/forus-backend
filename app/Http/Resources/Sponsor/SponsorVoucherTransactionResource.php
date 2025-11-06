<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\Tiny\ProductTinyResource;
use App\Http\Resources\VoucherTransactionNoteResource;
use App\Models\VoucherTransaction;
use App\Searches\VoucherTransactionsSearch;
use Illuminate\Http\Request;

/**
 * @property VoucherTransaction $resource
 */
class SponsorVoucherTransactionResource extends BaseJsonResource
{
    /**
     * @var string[]
     */
    public const array LOAD = [
        'voucher.fund:id,name,organization_id',
        'voucher.fund.organization.bank_connection_active.bank_connection_default_account',
        'voucher.product_reservation',
        'voucher_transaction_bulk',
        'product.photos.presets',
        'provider:id,name,iban',
        'notes_sponsor',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $transaction = $this->resource;

        return array_merge($transaction->only([
            'id', 'organization_id', 'product_id', 'state_locale', 'updated_at', 'address', 'state',
            'payment_id', 'voucher_transaction_bulk_id', 'attempts', 'iban_final',
            'target', 'target_locale', 'uid', 'voucher_id', 'description',
        ]), $this->getIbanFields($transaction), [
            'amount' => currency_format($transaction->amount),
            'amount_locale' => currency_format_locale($transaction->amount),
            'amount_extra_cash' => currency_format($transaction->amount_extra_cash),
            'amount_extra_cash_locale' => currency_format_locale($transaction->amount_extra_cash),
            'timestamp' => $transaction->created_at->timestamp,
            'transfer_in' => $transaction->daysBeforeTransaction(),
            'transfer_in_pending' => $transaction->transfer_at?->isFuture() && $transaction->isPending(),
            'organization' => $transaction->provider?->only('id', 'name'),
            'fund' => [
                ...$transaction->voucher->fund->only('id', 'name', 'organization_id'),
                'organization_name' => $transaction->voucher->fund->organization?->name,
            ],
            'notes' => VoucherTransactionNoteResource::collection($transaction->notes_sponsor),
            'bulk_status_locale' => $transaction->bulk_status_locale,
            'transaction_cost' => currency_format($transaction->transaction_cost),
            'transaction_cost_locale' => currency_format_locale($transaction->transaction_cost),
            'product' => new ProductTinyResource($transaction->product),
            'product_reservation' => $transaction->product_reservation?->only([
                'id', 'voucher_id',
            ]),
            'non_cancelable_at_locale' => format_date_locale($transaction->non_cancelable_at),
            'execution_date_locale' => format_date_locale($transaction->voucher_transaction_bulk?->execution_date),
            'bulk_state' => $transaction->voucher_transaction_bulk?->state,
            'bulk_state_locale' => $transaction->voucher_transaction_bulk?->state_locale,
            'payment_type_locale' => $this->getPaymentTypeLocale($transaction),
        ], $this->timestamps($transaction, 'created_at', 'transfer_at', 'updated_at'));
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

    /**
     * @param VoucherTransaction $transaction
     * @return array
     */
    public function getPaymentTypeLocale(VoucherTransaction $transaction): array
    {
        $key = $transaction['payment_type'];
        $params = ['product' => $transaction->product?->name];

        $key = $key ?: VoucherTransactionsSearch::appendSelectPaymentType(
            VoucherTransaction::whereId($transaction->id),
        )->pluck('payment_type')->first();

        return [
            'title' => trans("transaction.payment_type.$key.title", $params),
            'subtitle' => trans("transaction.payment_type.$key.subtitle", $params),
        ];
    }
}
