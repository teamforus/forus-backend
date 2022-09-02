<?php

namespace App\Http\Resources;

use App\Models\FundTopUpTransaction;

/**
 * @property FundTopUpTransaction $resource
 */
class TopUpTransactionResource extends BaseJsonResource
{
    public const LOAD = [
        'fund_top_up',
        'bank_connection_account',
    ];

    /**
     * Transform the resource into an array.
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $topUpTransaction = $this->resource;

        return array_merge($topUpTransaction->only('id'), [
            'code' => $topUpTransaction->fund_top_up->code,
            'iban' => $topUpTransaction->bank_connection_account?->monetary_account_iban,
            'amount' => currency_format($topUpTransaction->amount),
            'amount_locale' => currency_format_locale($topUpTransaction->amount),
        ], $this->timestamps($topUpTransaction, 'created_at'));
    }
}
