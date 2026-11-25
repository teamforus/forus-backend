<?php

namespace App\Http\Resources\Requester;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\ProductUserPriceResource;
use App\Http\Resources\Small\MediaSmallResource;
use App\Http\Resources\Traits\Products\FormatsProductPrices;
use App\Models\Fund;
use App\Models\FundProviderProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * @property-read Fund $resource
 */
class ProductFundResource extends BaseJsonResource
{
    use FormatsProductPrices;

    public const array LOAD_NESTED = [
        'logo' => MediaSmallResource::class,
    ];

    /**
     * @param Fund $fund
     * @param Product $product
     * @param int|null $limit
     * @param Carbon $expireAt
     * @param FundProviderProduct|null $fundProviderProduct
     * @param array $features
     */
    public function __construct(
        Fund $fund,
        private readonly Product $product,
        private readonly ?int $limit,
        private readonly Carbon $expireAt,
        private readonly ?FundProviderProduct $fundProviderProduct,
        public readonly array $features,
    ) {
        parent::__construct($fund);
    }

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $fund = $this->resource;
        $product = $this->product;

        return [
            ...$fund->only([
                'id',
            ]),
            ...$fund->translateColumns($fund->only([
                'name',
            ])),
            'logo' => $fund->logo ? new MediaSmallResource($fund->logo) : null,
            'limit' => $this->limit,
            'user_price' => new ProductUserPriceResource(
                $product,
                $this->fundPriceLabel($product, $this->fundProviderProduct),
            ),
            ...$this->features,
            ...$this->makeTimestamps([
                'end_at' => $fund->end_date,
                'expire_at' => $this->expireAt,
            ], dateOnly: true),
        ];
    }
}
