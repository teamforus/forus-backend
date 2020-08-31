<?php

namespace App\Http\Resources;

use App\Models\VoucherTransaction;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class VoucherTransactionResource
 * @property VoucherTransaction $resource
 * @package App\Http\Resources
 */
class VoucherTransactionResource extends Resource
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

        return array_merge([
            "id", "organization_id", "product_id", "address", "state", "payment_id",
        ], [
            'created_at' => $transaction->created_at ? $transaction->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $transaction->updated_at ? $transaction->updated_at->format('Y-m-d H:i:s') : null,
            'created_at_locale' => format_datetime_locale($transaction->created_at),
            'updated_at_locale' => format_datetime_locale($transaction->updated_at),

            'amount' => currency_format($transaction->amount),
            'timestamp' => $transaction->created_at->timestamp,
            "organization" => array_merge($transaction->provider->only([
                "id", "name"
            ]), [
                'logo' => new MediaResource($transaction->provider->logo),
            ]),
            "product" => new ProductResource($transaction->product),
            "fund" => array_merge($transaction->voucher->fund->only([
                "id", "name", "organization_id"
            ]), [
                'logo' => new MediaResource($transaction->voucher->fund->logo),
            ]),
        ]);
    }
}
