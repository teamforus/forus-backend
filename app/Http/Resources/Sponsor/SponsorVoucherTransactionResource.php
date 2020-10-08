<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\ProductResource;
use App\Http\Resources\VoucherTransactionNoteResource;
use App\Models\VoucherTransaction;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class SponsorVoucherTransactionResource
 * @property VoucherTransaction $resource
 * @package App\Http\Resources\Sponsor
 */
class SponsorVoucherTransactionResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $voucherTransaction = $this->resource;

        return collect($voucherTransaction)->only([
            "id", "organization_id", "product_id", "created_at",
            "updated_at", "address", "state", "payment_id",
        ])->merge([
            'created_at_locale' => format_datetime_locale($voucherTransaction->created_at),
            'updated_at_locale' => format_datetime_locale($voucherTransaction->updated_at),
            'amount' => currency_format($voucherTransaction->amount),
            'timestamp' => $voucherTransaction->created_at->timestamp,
            "organization" => collect($voucherTransaction->provider)->only([
                "id", "name"
            ]),
            "product" => new ProductResource($voucherTransaction->product),
            "fund" => collect($voucherTransaction->voucher->fund)->only([
                "id", "name", "organization_id"
            ]),
            'notes' => VoucherTransactionNoteResource::collection(
                $voucherTransaction->notes->where('group', 'sponsor')->values()
            )
        ])->toArray();
    }
}
