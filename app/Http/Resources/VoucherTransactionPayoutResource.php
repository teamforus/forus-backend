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
        return [
            ...$this->resource->only([
                'state', 'state_locale', 'iban_from',
            ]),
            'iban_to' => $this->resource->getTargetIban(),
            'iban_to_name' => $this->resource->getTargetName(),
            'amount' => currency_format($this->resource->amount),
            'amount_locale' => currency_format_locale($this->resource->amount),
            'fund' => [
                ...$this->resource->voucher->fund->only('id', 'name', 'organization_id'),
                'organization_name' => $this->resource->voucher->fund->organization?->name,
            ],
            ...$this->makeTimestamps($this->resource->only([
                'created_at', 'updated_at',
            ])),
        ];
    }
}
