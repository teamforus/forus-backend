<?php

namespace App\Http\Resources;

use App\Helpers\Arr;
use Illuminate\Http\Request;

class VoucherTransactionPayoutResource extends VoucherTransactionResource
{
    /**
     * @var string[]
     */
    public const array LOAD = [
        'voucher.fund.organization',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $fund = $this->resource->voucher->fund;

        return [
            ...$this->resource->only([
                'state', 'state_locale', 'iban_from',
            ]),
            'iban_to' => $this->resource->getTargetIban(),
            'iban_to_name' => $this->resource->getTargetName(),
            'amount' => currency_format($this->resource->amount),
            'amount_locale' => currency_format_locale($this->resource->amount),
            'expired' => $this->resource->voucher->expired,
            'fund' => [
                ...$fund->only([
                    'id', 'name', 'organization_id',
                ]),
                $fund->translateColumns($fund->only([
                    'name',
                ])),
                'organization_name' => $fund->organization->name,
            ],
            ...$this->makeTimestamps($this->resource->only([
                'created_at', 'updated_at',
            ])),
        ];
    }
}
