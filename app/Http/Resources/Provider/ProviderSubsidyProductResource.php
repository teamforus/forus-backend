<?php

namespace App\Http\Resources\Provider;

use App\Http\Resources\MediaResource;
use App\Http\Resources\OrganizationBasicResource;
use App\Http\Resources\ProductCategoryResource;
use App\Models\FundProviderProduct;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ProviderSubsidyProductResource
 * @property FundProviderProduct $resource
 * @package App\Http\Resources\Provider
 */
class ProviderSubsidyProductResource extends JsonResource
{
    public static $load = [
        'product.photo.presets',
        'product.product_category.translations',
        'product.organization.logo.presets',
        'product.organization.business_type.translations',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request|any $request
     * @return array
     */
    public function toArray($request): array
    {
        $fundProviderProduct = $this->resource;
        $product = $fundProviderProduct->product;

        $price = $product->price;
        $price_user = $product->price - $fundProviderProduct->amount;

        return array_merge($product->only([
            'id', 'name', 'sold_out', 'organization_id', 'expired', 'unlimited_stock', 'no_price',
            'no_price_type', 'no_price_discount'
        ]), [
            'price' => currency_format($price),
            'price_user' => currency_format($price_user),
            'price_old' => $product->old_price ? currency_format($product->old_price) : null,

            'expire_at' => $product->expire_at->format('Y-m-d'),
            'expire_at_locale' => format_date_locale($product->expire_at ?? null),

            'photo' => new MediaResource($product->photo),
            'organization' => new OrganizationBasicResource($product->organization),
            'product_category' => new ProductCategoryResource($product->product_category),
        ]);
    }
}
