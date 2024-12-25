<?php

namespace App\Http\Resources\Provider;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\OrganizationBasicResource;
use App\Http\Resources\ProductCategoryResource;
use App\Models\FundProviderProduct;
use Illuminate\Http\Request;

/**
 * @property FundProviderProduct $resource
 */
class ProviderSubsidyProductResource extends BaseJsonResource
{
    public const array LOAD = [
        'fund_provider.fund.organization',
        'product.photo.presets',
        'product.product_category.translations',
        'product.organization.logo.presets',
        'product.organization.business_type.translations',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $fundProviderProduct = $this->resource;
        $product = $fundProviderProduct->product;
        $fund = $fundProviderProduct->fund_provider->fund;
        $payment_required = $product->price_type === $product::PRICE_TYPE_REGULAR;

        $price = $product->price;
        $price_user = max($product->price - $fundProviderProduct->amount, 0);
        $price_user_local = $payment_required ? 'Prijs: ' . currency_format_locale($price_user) : $product->price_locale;
        $sponsor_subsidy = $fundProviderProduct->amount;

        return array_merge($product->only([
            'id', 'name', 'sold_out', 'organization_id', 'expired', 'unlimited_stock',
            'price_type', 'price_discount',
        ]), [
            'price' => currency_format($price),
            'price_locale' => $product->price_locale,
            'price_user' => currency_format($price_user),
            'price_user_locale' => $price_user_local,
            'sponsor_subsidy' => currency_format($sponsor_subsidy),
            'sponsor_subsidy_locale' => currency_format_locale($sponsor_subsidy),

            'expire_at' => $product->expire_at ? $product->expire_at->format('Y-m-d') : '',
            'expire_at_locale' => format_date_locale($product->expire_at ?? null),

            'photo' => new MediaResource($product->photo),
            'sponsor' => new OrganizationBasicResource($fund->organization),
            'organization' => new OrganizationBasicResource($product->organization),
            'product_category' => new ProductCategoryResource($product->product_category),
        ]);
    }
}
