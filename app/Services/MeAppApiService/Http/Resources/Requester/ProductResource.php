<?php

namespace App\Services\MeAppApiService\Http\Resources\Requester;

use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\BaseJsonResource;
use App\Models\Fund;
use App\Models\FundProviderProduct;
use App\Models\Implementation;
use App\Models\Product;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\ProductSubQuery;
use App\Services\MeAppApiService\Http\Resources\Shared\MediaResource;
use App\Services\MeAppApiService\Http\Resources\Shared\ProductCategoryResource;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * @property-read Product $resource
 */
class ProductResource extends BaseJsonResource
{
    public const array LOAD = [
        'organization.fund_providers',
        'organization.fund_providers_allowed_extra_payments',
        'organization.fund_providers_allowed_extra_payments_full',
        'organization.mollie_connection',
        'bookmarks',
    ];

    public const array LOAD_NESTED = [
        'photos' => MediaResource::class,
        'product_category' => ProductCategoryResource::class,
        'organization' => ProviderResource::class,
        'organization.offices' => ProductOfficeResource::class,
    ];

    /**
     * @param Request $request
     * @throws Exception
     * @return array
     */
    public function toArray(Request $request): array
    {
        return $this->getProductData($request, $this->resource);
    }

    /**
     * @param Request $request
     * @param Product $product
     * @throws Exception
     * @return array
     */
    protected function getProductData(Request $request, Product $product): array
    {
        $baseRequest = BaseFormRequest::createFrom($request);

        $priceFields = $this->priceFields($product);
        $priceFieldsExtra = $this->priceFieldsExtra($product);

        return [
            ...$this->baseFields($product),
            'photos' => MediaResource::collection($product->photos),
            'organization' => new ProviderResource($product->organization),
            'funds' => $this->getProductFunds($baseRequest, $product),
            'offices' => ProductOfficeResource::collection($product->organization->offices),
            'product_category' => $product->product_category
                ? new ProductCategoryResource($product->product_category)
                : null,
            'bookmarked' => $product->isBookmarkedBy($baseRequest->identity()),
            ...$priceFields,
            ...$priceFieldsExtra,
            ...$this->makeTimestamps($product->only([
                'expire_at',
            ]), dateOnly: true),
        ];
    }

    /**
     * @param Product $product
     * @return array
     */
    protected function baseFields(Product $product): array
    {
        return [
            ...$product->only([
                'id', 'name', 'product_category_id', 'sold_out', 'organization_id', 'alternative_text',
                'description_html',
                'info_duration', 'info_when', 'info_where', 'info_more_info', 'info_attention',
            ]),
            ...$product->translateColumns($product->only([
                'name', 'description_html',
                'info_duration', 'info_when', 'info_where', 'info_more_info', 'info_attention',
            ])),
            'price' => is_null($product->price) ? null : currency_format($product->price),
            'price_locale' => $product->price_locale,
        ];
    }

    /**
     * @param Product $product
     * @return array
     */
    protected function priceFields(Product $product): array
    {
        return [
            'price_type' => $product->price_type,
            'price_discount' => $product->price_discount ? currency_format($product->price_discount) : null,
        ];
    }

    /**
     * @param Product $product
     * @return array
     */
    protected function priceFieldsExtra(Product $product): array
    {
        $price_min = $this->getProductSubsidyPrice($product, 'max');
        $price_max = $this->getProductSubsidyPrice($product, 'min');

        return [
            'price_discount_locale' => $product->price_discount_locale,
            'price_min' => currency_format($price_min),
            'price_min_locale' => currency_format_locale($price_min),
            'price_max' => currency_format($price_max),
            'price_max_locale' => currency_format_locale($price_max),
        ];
    }

    /**
     * @return Builder
     */
    protected function fundsQuery(): Builder
    {
        return Implementation::activeFundsQuery();
    }

    /**
     * @param BaseFormRequest $request
     * @param Product $product
     * @throws Exception
     * @return Collection<int, ProductFundResource>
     */
    private function getProductFunds(BaseFormRequest $request, Product $product): Collection
    {
        $fundsQuery = FundQuery::whereProductsAreApprovedAndActiveFilter($this->fundsQuery(), $product);
        $fundsQuery->with(ProductFundResource::load());

        return $fundsQuery->get()->map(function (Fund $fund) use ($product, $request): ProductFundResource {
            $fundProviderProduct = $product->getFundProviderProduct($fund);

            $productData = ProductSubQuery::appendReservationStats([
                'identity_id' => $request->auth_id(),
                'fund_id' => $fund->id,
            ], Product::whereId($product->id))->firstOrFail()->only([
                'limit_available',
            ]);

            return new ProductFundResource(
                $fund,
                $product,
                $productData['limit_available'],
                $fundProviderProduct,
            );
        })->values();
    }

    /**
     * @param Product $product
     * @param string $type
     * @return float
     */
    private function getProductSubsidyPrice(Product $product, string $type): float
    {
        return max($product->price - $product->fund_provider_products()->where([
            'product_id' => $product->id,
            'payment_type' => FundProviderProduct::PAYMENT_TYPE_SUBSIDY,
        ])->whereHas('fund_provider.fund', function (Builder $builder) {
            $builder->whereIn('funds.id', $this->fundsQuery()->select('funds.id'));
        })->$type('amount'), 0);
    }
}
