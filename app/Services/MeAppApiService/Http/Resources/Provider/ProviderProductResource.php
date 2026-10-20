<?php

namespace App\Services\MeAppApiService\Http\Resources\Provider;

use App\Http\Resources\BaseJsonResource;
use App\Models\FundProviderProduct;
use App\Models\Product;
use App\Models\Voucher;
use App\Services\MeAppApiService\Http\Resources\Shared\MediaResource;
use App\Services\MeAppApiService\Http\Resources\Shared\OrganizationResource;
use Illuminate\Http\Request;

/**
 * @property-read Product $resource
 */
class ProviderProductResource extends BaseJsonResource
{
    public const array LOAD_NESTED = [
        'photos' => MediaResource::class,
        'organization' => OrganizationResource::class,
    ];

    protected ?Voucher $voucher = null;

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $product = $this->resource;
        $fundProviderProduct = $product->getFundProviderProduct($this->voucher->fund);

        if ($fundProviderProduct) {
            $fundProviderProduct->loadMissing([
                ...static::load('product'),
                'product.product_category.translations',
                'fund_provider.fund.organization',
                ...OrganizationResource::load('fund_provider.fund.organization'),
            ]);

            return $this->toArrayFundProviderProduct($fundProviderProduct);
        }

        return [
            ...$product->only([
                'id', 'name', 'description', 'product_category_id', 'organization_id',
            ]),
            ...$product->translateColumns($product->only([
                'name',
            ])),
            'price' => $product->price === null ? null : currency_format($product->price),
            'price_locale' => $product->price_locale,
            'photos' => MediaResource::collection($product->photos),
            'organization' => new OrganizationResource($product->organization),
            'price_user' => currency_format(0),
            'price_user_locale' => 'Gratis',
            'sponsor_subsidy' => $product->price === null ? null : currency_format($product->price),
            'sponsor_subsidy_locale' => $product->price_locale,
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

        $price_user_local = $payment_required ?
            'Prijs: ' . currency_format_locale($price_user) :
            $product->price_locale;

        $sponsor_subsidy = $fundProviderProduct->amount;

        return [
            ...$product->only([
                'id', 'name', 'organization_id', 'price_type', 'price_discount',
            ]),
            'price' => currency_format($price),
            'price_locale' => $product->price_locale,
            'price_user' => currency_format($price_user),
            'price_user_locale' => $price_user_local,
            'sponsor_subsidy' => currency_format($sponsor_subsidy),
            'sponsor_subsidy_locale' => currency_format_locale($sponsor_subsidy),
            'photos' => MediaResource::collection($product->photos),
            'sponsor' => new OrganizationResource($fund->organization),
            'organization' => new OrganizationResource($product->organization),
            'product_category' => [
                'id' => $product->product_category->id,
                'key' => $product->product_category->key,
                /** @var string|null */
                'name' => $product->product_category->name,
            ],
        ];
    }
}
