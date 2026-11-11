<?php

namespace App\Http\Resources;

use App\Http\Resources\Small\FundSmallResource;
use App\Models\FundProductLimit;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * @property FundProductLimit $resource
 */
class FundProductLimitResource extends BaseJsonResource
{
    public const array LOAD = [
        'products',
    ];

    public const array LOAD_NESTED = [
        'fund' => FundSmallResource::class,
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->only([
                'id', 'state', 'fund_id', 'type', 'limit_by',
            ]),
            'limit' => $this->resource->limit_by === FundProductLimit::LIMIT_BY_AMOUNT
                ? currency_format($this->resource->limit)
                : (int) $this->resource->limit,
            'limit_locale' => $this->resource->limit_by === FundProductLimit::LIMIT_BY_AMOUNT
                ? currency_format_locale($this->resource->limit)
                : (string) (int) $this->resource->limit,
            'type_locale' => trans("fund_product_limits.types.{$this->resource->type}"),
            'limit_by_locale' => trans("fund_product_limits.limit_by.{$this->resource->limit_by}"),
            'fund' => FundSmallResource::create($this->resource->fund),
            'products' => $this->resource->products->map(function (Product $product) {
                return $product->only(['id', 'name']);
            }),
            ...$this->makeTimestamps($this->resource->only(['created_at'])),
        ];
    }
}
