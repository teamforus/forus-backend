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
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $transaction = $this->resource;

        return collect($transaction)->only([
            "id", "organization_id", "product_id", "created_at",
            "updated_at", "address", "state", "payment_id",
            'created_at_locale', 'created_at_locale'
        ])->merge([
            'amount' => currency_format($transaction->amount),
            'timestamp' => $transaction->created_at->timestamp,
            "organization" => collect($transaction->provider)->only([
                "id", "name"
            ])->merge([
                'logo' => new MediaResource($transaction->provider->logo),
            ]),
            "product" => new ProductResource($transaction->product),
            "fund" => collect($transaction->voucher->fund)->only([
                "id", "name", "organization_id"
            ])->merge([
                'logo' => new MediaResource($transaction->voucher->fund->logo),
            ]),
        ])->toArray();
    }
}
