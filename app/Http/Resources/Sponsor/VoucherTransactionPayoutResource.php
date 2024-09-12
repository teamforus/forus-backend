<?php

namespace App\Http\Resources\Sponsor;

use App\Models\VoucherTransaction;
use Illuminate\Http\Request;

/**
 * @property VoucherTransaction $resource
 */
class VoucherTransactionPayoutResource extends SponsorVoucherTransactionResource
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
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray(Request $request): array
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
            'is_editable' => $transaction->isEditablePayout(),
            'is_cancelable' => $transaction->isCancelableBySponsor(),
            'payout_relations' => $transaction->payout_relations->map(fn ($relation) => $relation->only([
                'id', 'type', 'value',
            ])),
            'amount_preset_id' => $transaction->voucher?->fund_amount_preset_id,
            'payment_type_locale' => $this->getPaymentTypeLocale($transaction),
        ];
    }
}
