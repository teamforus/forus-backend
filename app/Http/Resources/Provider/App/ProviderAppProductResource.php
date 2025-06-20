<?php

namespace App\Http\Resources\Provider\App;

use App\Http\Resources\MediaResource;
use App\Http\Resources\OrganizationBasicResource;
use App\Http\Resources\ProductCategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\FundProviderProduct;
use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Http\Request;

/**
 * @property Product|null $resource
 * @property Voucher|null $voucher
 * @property bool|null $reservable
 */
class ProviderAppProductResource extends ProductResource
{
    public const array LOAD = [
        'fund_provider_products.fund_provider.fund.organization',
        'fund_provider_products.product.photo.presets',
        'fund_provider_products.product.product_category.translations',
        'fund_provider_products.product.organization.logo.presets',
        'fund_provider_products.product.organization.business_type.translations',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $data = $this->baseFields($this->resource);
        $fundProviderProduct = $this->resource->getFundProviderProduct($this->voucher->fund);

        if ($fundProviderProduct) {
            return $this->toArrayFundProviderProduct($fundProviderProduct);
        }

        return [
            ...$data,
            'photo' => new MediaResource($this->resource->photo),
            'organization' => new OrganizationBasicResource($this->resource->organization),
            'description_html' => $this->resource->description_html,
            'price_user' => currency_format(0),
            'price_user_locale' => 'Gratis',
            'sponsor_subsidy' => array_get($data, 'price'),
            'sponsor_subsidy_locale' => array_get($data, 'price_locale'),
        ];
    }

    /**
     * Transform the resource into an array.
     *
     * @param FundProviderProduct $fundProviderProduct
     * @return array
     */
    public function toArrayFundProviderProduct(FundProviderProduct $fundProviderProduct): array
    {
        $product = $fundProviderProduct->product;
        $fund = $fundProviderProduct->fund_provider->fund;
        $payment_required = $product->price_type === $product::PRICE_TYPE_REGULAR;

        $price = $product->price;
        $price_user = max($product->price - $fundProviderProduct->amount, 0);
        $price_user_local = $payment_required ? 'Prijs: ' . currency_format_locale($price_user) : $product->price_locale;
        $sponsor_subsidy = $fundProviderProduct->amount;

        return [
            ...$product->only([
                'id', 'name', 'sold_out', 'organization_id', 'expired', 'unlimited_stock',
                'price_type', 'price_discount',
            ]),
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
        ];
    }
}
