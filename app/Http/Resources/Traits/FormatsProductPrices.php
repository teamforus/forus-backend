<?php

namespace App\Http\Resources\Traits;

use App\Models\Fund;
use App\Models\FundProviderProduct;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

trait FormatsProductPrices
{
    /**
     * @param Product $product
     * @return array
     */
    protected function basePriceFields(Product $product): array
    {
        return [
            'price' => is_null($product->price) ? null : currency_format($product->price),
            'price_locale' => $product->price_locale,
        ];
    }

    /**
     * @param Product $product
     * @param FundProviderProduct|null $deal
     * @return string
     */
    protected function fundPriceLabel(Product $product, ?FundProviderProduct $deal): string
    {
        $price = $product->price;

        if ($product->price_type === Product::PRICE_TYPE_REGULAR && $deal?->isPaymentTypeSubsidy()) {
            $price = $deal->getUserPrice($price);
        }

        return $product->minimumPriceLocale(currency_format($price));
    }

    /**
     * @param Product $product
     * @param Builder<Fund> $funds
     * @return array
     */
    protected function minimumPriceFields(Product $product, Builder $funds): array
    {
        $minimumPrice = currency_format($product->minimumPriceForFunds($funds));

        return [
            'price_min' => $minimumPrice,
            'price_min_locale' => $product->minimumPriceLocale($minimumPrice),
        ];
    }
}
