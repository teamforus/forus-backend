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
        'voucher.fund.organization',
    ];

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
                'state', 'state_locale', 'iban_from',
            ]),
            'iban_to' => $transaction->getTargetIban(),
            'iban_to_name' => $transaction->getTargetName(),
            'amount' => currency_format($transaction->amount),
            'amount_locale' => currency_format_locale($transaction->amount),
            'fund' => [
                ...$transaction->voucher->fund->only('id', 'name', 'organization_id'),
                'organization_name' => $transaction->voucher->fund->organization?->name,
            ],
            ...$this->makeTimestamps($transaction->only([
                'created_at', 'updated_at',
            ])),
        ];
    }
}
