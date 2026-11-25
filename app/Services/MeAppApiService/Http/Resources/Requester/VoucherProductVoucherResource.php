<?php

namespace App\Services\MeAppApiService\Http\Resources\Requester;

use App\Http\Resources\BaseJsonResource;
use App\Models\Voucher;
use Illuminate\Http\Request;

/**
 * @property-read Voucher $resource
 */
class VoucherProductVoucherResource extends BaseJsonResource
{
    public const array LOAD = [
        'product',
    ];

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $product_voucher = $this->resource;

        return [
            'id' => $product_voucher->id,
            'amount' => currency_format($product_voucher->amount),
            'amount_locale' => currency_format_locale($product_voucher->amount),
            'product' => $product_voucher->product->only([
                'id', 'name', 'organization_id',
            ]),
            ...$this->makeTimestamps($product_voucher->only([
                'created_at',
            ])),
        ];
    }
}
