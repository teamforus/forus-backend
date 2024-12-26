<?php

namespace App\Http\Resources;

use App\Models\VoucherTransaction;

/**
 * @property VoucherTransaction $resource
 */
class VoucherTransactionResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $transaction = $this->resource;

        return array_merge($transaction->only([
            'id', 'organization_id', 'product_id', 'address', 'state', 'state_locale', 'payment_id', 'target',
        ]), [
            'cancelable' => $transaction->isCancelable(),
            'transfer_in' => $transaction->daysBeforeTransaction(),
            'amount' => currency_format($transaction->amount),
            'amount_locale' => currency_format_locale(
                $transaction->amount,
                $transaction->voucher->fund->getImplementation(),
            ),
            'amount_extra_cash' => currency_format($transaction->amount_extra_cash),
            'amount_extra_cash_locale' => currency_format_locale(
                $transaction->amount_extra_cash,
                $transaction->voucher->fund->getImplementation(),
            ),
            'timestamp' => $transaction->created_at->timestamp,
            'organization' => $transaction->provider ? array_merge($transaction->provider->only([
                'id', 'name'
            ]), [
                'logo' => new MediaResource($transaction->provider->logo),
            ]) : null,
            'product' => new ProductResource($transaction->product),
            'fund' => array_merge($transaction->voucher->fund->only([
                'id', 'name', 'organization_id'
            ]), [
                'logo' => new MediaResource($transaction->voucher->fund->logo),
            ]),
        ], $this->timestamps($transaction, 'created_at', 'updated_at', 'transfer_at'));
    }
}
