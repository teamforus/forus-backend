<?php

namespace App\Http\Resources\Provider;

use App\Http\Resources\MediaResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\VoucherTransactionNoteResource;
use App\Models\VoucherTransaction;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class ProviderVoucherTransactionResource
 * @property VoucherTransaction $resource
 * @package App\Http\Resources\Provider
 */
class ProviderVoucherTransactionResource extends Resource
{
    public static $load = [
        'provider',
        'provider.business_type.translations',
        'provider.logo.sizes',
        'voucher.fund',
        'voucher.fund.logo.sizes',
        'product',
        'notes',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $voucherTransaction = $this->resource;

        return collect($voucherTransaction)->only([
            "id", "organization_id", "product_id", "created_at",
            "updated_at", "address", "state", "payment_id",
        ])->merge([
            'created_at_locale' => $voucherTransaction->created_at_locale,
            'updated_at_locale' => $voucherTransaction->updated_at_locale,
            'amount' => currency_format($voucherTransaction->amount),
            'timestamp' => $voucherTransaction->created_at->timestamp,
            "organization" => collect($voucherTransaction->provider)->only([
                "id", "name"
            ])->merge([
                'logo' => new MediaResource($voucherTransaction->provider->logo),
            ]),
            "product" => new ProductResource($voucherTransaction->product),
            "fund" => collect($voucherTransaction->voucher->fund)->only([
                "id", "name", "organization_id"
            ])->merge([
                'logo' => new MediaResource($voucherTransaction->voucher->fund->logo),
            ]),
            'notes' => VoucherTransactionNoteResource::collection(
                $voucherTransaction->notes->where('group', 'provider')->values()
            )
        ])->toArray();
    }
}
