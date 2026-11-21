<?php

namespace App\Models\Enums;

use App\Models\Product;

enum ProductPriceType: string
{
    case Free = Product::PRICE_TYPE_FREE;
    case Regular = Product::PRICE_TYPE_REGULAR;
    case Informational = Product::PRICE_TYPE_INFORMATIONAL;
    case DiscountFixed = Product::PRICE_TYPE_DISCOUNT_FIXED;
    case DiscountPercentage = Product::PRICE_TYPE_DISCOUNT_PERCENTAGE;

    /**
     * @param Product $product
     * @return self
     */
    public static function forProduct(Product $product): self
    {
        return self::from($product->price_type);
    }
}
