<?php

namespace App\Http\Resources;

use App\Models\VoucherTransaction;

/**
 * Class VoucherTransactionResource
 * @property VoucherTransaction $resource
 * @package App\Http\Resources
 */
class VoucherTransactionResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request|any  $request
     * @return array
     */
    public function toArray($request): array
    {
        $transaction = $this->resource;

        return array_merge($transaction->only([
            'id', 'organization_id', 'product_id', 'address', 'state', 'payment_id', 'target',
        ]), [
            'cancelable' => $transaction->isCancelable(),
            'transaction_in' => $transaction->daysBeforeTransaction(),
            'amount' => currency_format($transaction->amount),
            'timestamp' => $transaction->created_at->timestamp,
            'organization' => $transaction->provider ? array_merge($transaction->provider->only([
                'id', 'name'
            ]), [
                'logo' => new MediaResource($transaction->provider->logo),
            ]) : [],
            'product' => new ProductResource($transaction->product),
            'fund' => array_merge($transaction->voucher->fund->only([
                'id', 'name', 'organization_id'
            ]), [
                'logo' => new MediaResource($transaction->voucher->fund->logo),
            ]),
        ], $this->timestamps($transaction, 'created_at', 'updated_at', 'transfer_at'));
    }
}
