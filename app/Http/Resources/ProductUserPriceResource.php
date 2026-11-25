<?php

namespace App\Http\Resources;

use App\Models\Enums\ProductPriceType;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * @property-read Product $resource
 */
class ProductUserPriceResource extends BaseJsonResource
{
    /**
     * @param Product $product
     * @param string $value
     */
    public function __construct(Product $product, private readonly string $value)
    {
        parent::__construct($product);
    }

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => ProductPriceType::forProduct($this->resource),
            'value' => $this->value,
        ];
    }
}
