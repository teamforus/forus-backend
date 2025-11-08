<?php

namespace App\Http\Resources;

use App\Http\Resources\Small\ProductSmallResource;
use App\Models\VoucherTransaction;
use Illuminate\Http\Request;

/**
 * @property VoucherTransaction $resource
 */
class VoucherTransactionResource extends BaseJsonResource
{
    /**
     * @var array
     */
    public const array LOAD = [
        'voucher_transaction_bulk',
        'provider.logo.presets',
        'voucher.fund.logo.presets',
        'product.organization',
        'product.photos.presets',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $transaction = $this->resource;

        return [
            ...$transaction->only([
                'id', 'organization_id', 'product_id', 'address', 'state', 'state_locale', 'payment_id', 'target',
            ]),
            'cancelable' => $transaction->isCancelable(),
            'transfer_in' => $transaction->daysBeforeTransaction(),
            'amount' => currency_format($transaction->amount),
            'amount_locale' => currency_format_locale($transaction->amount),
            'amount_extra_cash' => currency_format($transaction->amount_extra_cash),
            'amount_extra_cash_locale' => currency_format_locale($transaction->amount_extra_cash),
            'timestamp' => $transaction->created_at->timestamp,
            'organization' => $transaction->provider ? [
                ...$transaction->provider->only(['id', 'name']),
                'logo' => new MediaResource($transaction->provider->logo),
            ] : null,
            'product' => new ProductSmallResource($transaction->product),
            'fund' => [
                ...$transaction->voucher->fund->only(['id', 'name', 'organization_id']),
                'logo' => new MediaResource($transaction->voucher->fund->logo),
            ],
            ...$this->timestamps($transaction, 'created_at', 'updated_at', 'transfer_at'),
        ];
    }
}
