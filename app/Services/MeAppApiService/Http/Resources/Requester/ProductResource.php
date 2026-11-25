<?php

namespace App\Services\MeAppApiService\Http\Resources\Requester;

use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\ProductUserPriceResource;
use App\Http\Resources\Small\MediaSmallResource;
use App\Http\Resources\Traits\Products\FormatsProductPrices;
use App\Http\Resources\Traits\Products\ResolvesProductFeatures;
use App\Http\Resources\Traits\Products\ResolvesProductFunds;
use App\Models\Implementation;
use App\Models\Product;
use App\Services\MeAppApiService\Http\Resources\MobileBaseJsonResource;
use App\Services\MeAppApiService\Http\Resources\Shared\ProductCategoryResource;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * @property-read Product $resource
 */
class ProductResource extends MobileBaseJsonResource
{
    use FormatsProductPrices;
    use ResolvesProductFeatures;
    use ResolvesProductFunds;

    public const array LOAD = [
        'organization.fund_providers',
        'organization.fund_providers_allowed_extra_payments',
        'organization.fund_providers_allowed_extra_payments_full',
        'organization.mollie_connection',
        'bookmarks',
    ];

    public const array LOAD_NESTED = [
        'photos' => MediaSmallResource::class,
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
     * @return array
     */
    protected function getProductData(Request $request, Product $product): array
    {
        $baseRequest = BaseFormRequest::createFrom($request);
        $minimumPriceFields = $this->minimumPriceFields($product, $this->fundsQuery());
        $funds = $this->getProductFunds($baseRequest, $product);

        return [
            ...$this->baseFields($product),
            ...$this->productFeatures($funds),
            'bookmarked' => $product->isBookmarkedBy($baseRequest->identity()),
            'user_price' => new ProductUserPriceResource($product, $minimumPriceFields['price_min_locale']),
            'photos' => MediaSmallResource::collection($product->photos),
            'product_category' => $product->product_category
                ? new ProductCategoryResource($product->product_category)
                : null,
            'funds' => $funds,
            'offices' => ProductOfficeResource::collection($product->organization->offices),
            'organization' => new ProviderResource($product->organization),
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
                'id', 'name', 'sold_out', 'organization_id', 'alternative_text', 'description_html',
                'info_duration', 'info_when', 'info_where', 'info_more_info', 'info_attention',
            ]),
            ...$product->translateColumns($product->only([
                'name', 'description_html',
                'info_duration', 'info_when', 'info_where', 'info_more_info', 'info_attention',
            ])),
        ];
    }

    /**
     * @return Builder
     */
    protected function fundsQuery(): Builder
    {
        return Implementation::activeFundsQuery();
    }
}
