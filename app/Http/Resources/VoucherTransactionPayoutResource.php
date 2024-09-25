<?php

namespace App\Http\Resources;

use App\Models\VoucherTransaction;
use App\Searches\VoucherTransactionsSearch;

class VoucherTransactionPayoutResource extends VoucherTransactionResource
{
    /**
     * @var string[]
     */
    public const LOAD = [
        'voucher.fund:id,name',
        'voucher.fund.organization.bank_connection_active.bank_connection_default_account',
        'employee.identity.primary_email',
        'payout_relations',
    ];

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

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $transaction = $this->resource;

        return [
            ...$transaction->only([
                'id', 'state', 'state_locale', 'address', 'employee_id',
                'upload_batch_id', 'iban_final', 'target', 'target_locale',  'description',
            ]),
            ...$this->getIbanFields($transaction),
            ...$this->timestamps($transaction, [
                'created_at', 'transfer_at', 'updated_at',
            ]),
            'amount' => currency_format($transaction->amount),
            'amount_locale' => currency_format_locale($transaction->amount),
            'transfer_in' => $transaction->daysBeforeTransaction(),
            'transfer_in_pending' => $transaction->transfer_at?->isFuture() && $transaction->isPending(),
            'fund' => [
                ...$transaction->voucher->fund->only('id', 'name', 'organization_id'),
                'organization_name' => $transaction->voucher->fund->organization?->name,
            ],
            'employee' => $transaction->employee?->identity?->only([
                'id', 'email', 'address',
            ]),
            'payout_relations' => $transaction->payout_relations->map(fn ($relation) => $relation->only([
                'id', 'type', 'value',
            ])),
            'amount_preset_id' => $transaction->voucher?->fund_amount_preset_id,
            'payment_type_locale' => $this->getPaymentTypeLocale($transaction),
        ];
    }
}
