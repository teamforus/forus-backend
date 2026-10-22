<?php

namespace App\Services\MeAppApiService\Http\Resources\Provider;

use App\Http\Resources\BaseJsonResource;
use App\Models\VoucherTransaction;
use Illuminate\Http\Request;

/**
 * @property-read VoucherTransaction $resource
 */
class VoucherTransactionResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $transaction = $this->resource;

        return [
            ...$transaction->only([
                'id', 'organization_id', 'product_id', 'state', 'state_locale', 'target',
            ]),
            'amount' => currency_format($transaction->amount),
            'amount_locale' => currency_format_locale($transaction->amount),
            'amount_extra_cash' => currency_format($transaction->amount_extra_cash),
            'amount_extra_cash_locale' => currency_format_locale($transaction->amount_extra_cash),
            ...$this->makeTimestamps($transaction->only([
                'created_at',
            ])),
        ];
    }
}
