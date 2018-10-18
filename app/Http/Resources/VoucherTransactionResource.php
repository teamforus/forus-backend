<?php

namespace App\Http\Resources;

use App\Models\VoucherTransaction;
use Illuminate\Http\Resources\Json\Resource;

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
        /** @var VoucherTransaction $voucherTransaction */
        $voucherTransaction = $this->resource;

        return collect($voucherTransaction)->only([
            "organization_id", "product_id", "amount", "created_at",
            "updated_at", "address", "state", "payment_id"
        ])->merge([
            'date' => $voucherTransaction->created_at->format('M d, Y'),
            'date_time' => $voucherTransaction->created_at->format('M d, Y H:i'),
            'timestamp' => $voucherTransaction->created_at->timestamp,
            "organization" => collect($voucherTransaction->organization)->only([
                "id", "name"
            ]),
            "product" => new ProductResource($voucherTransaction->product),
            "fund" => collect($voucherTransaction->voucher->fund)->only([
                "id", "name", "organization_id"
            ]),
        ])->toArray();
    }
}
