<?php

namespace App\Http\Resources;

use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\Traits\FormatsProductPrices;
use App\Http\Resources\Traits\ResolvesProductFeatures;
use App\Http\Resources\Traits\ResolvesProductFundLimits;
use App\Models\Fund;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * @property Product $resource
 */
abstract class ProductResource extends BaseJsonResource
{
    use FormatsProductPrices;
    use ResolvesProductFeatures;
    use ResolvesProductFundLimits;

    public const array LOAD = [
        'voucher_transactions',
        'product_reservations_pending',
        'organization.fund_providers',
        'organization.fund_providers_allowed_extra_payments',
        'organization.fund_providers_allowed_extra_payments_full',
        'organization.mollie_connection',
        'bookmarks',
    ];

    public const array LOAD_NESTED = [
        'product_category' => ProductCategoryResource::class,
        'organization' => OrganizationBasicResource::class,
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $baseRequest = BaseFormRequest::createFrom($request);

        return $this->getProductData($request, $this->resource, $baseRequest->isWebshop());
    }

    /**
     * @param Request $request
     * @param Product $product
     * @param bool $forWebshop
     * @return array
     */
    protected function getProductData(Request $request, Product $product, bool $forWebshop): array
    {
        $baseRequest = BaseFormRequest::createFrom($request);

        $priceFields = $this->priceFields($product, $forWebshop);
        $minimumPriceFields = $forWebshop ? $this->minimumPriceFields($product, $this->fundsQuery()) : [];

        return [
            ...$this->baseFields($product),
            'organization' => new OrganizationBasicResource($product->organization),
            'total_amount' => $product->total_amount,
            'unlimited_stock' => $product->unlimited_stock,
            'reserved_amount' => $product->countReservedCached(),
            'sold_amount' => $product->countSoldCached(),
            'stock_amount' => $product->stock_amount,
            'expire_at' => $product->expire_at?->format('Y-m-d'),
            'expire_at_locale' => format_date_locale($product->expire_at ?? null),
            'expired' => $product->expired,
            'deleted_at' => $product->deleted_at?->format('Y-m-d'),
            'deleted_at_locale' => format_date_locale($product->deleted_at ?? null),
            'deleted' => $product->trashed(),
            'funds' => $product->trashed() || !$forWebshop ? [] : $this->getProductFunds($baseRequest, $product),
            'product_category' => $product->product_category
                ? new ProductCategoryResource($product->product_category)
                : null,
            'bookmarked' => $forWebshop ? $product->isBookmarkedBy($baseRequest->identity()) : null,
            ...$priceFields,
            ...$minimumPriceFields,
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
                'id', 'name', 'description', 'product_category_id', 'sold_out', 'qr_enabled',
                'organization_id', 'reservation_enabled', 'reservation_policy', 'alternative_text',
                'description_html',
                'info_duration', 'info_when', 'info_where', 'info_more_info', 'info_attention',
            ]),
            ...$product->translateColumns(
                $this->isCollection()
                    ? $product->only(['name'])
                    : $product->only([
                        'name', 'description_html',
                        'info_duration', 'info_when', 'info_where', 'info_more_info', 'info_attention',
                    ]),
            ),
            ...$this->basePriceFields($product),
            'organization' => $product->organization->only('id', 'name'),
        ];
    }

    /**
     * @param Product $product
     * @param bool $forWebshop
     * @return array
     */
    protected function priceFields(Product $product, bool $forWebshop): array
    {
        return [
            'price_type' => $product->price_type,
            ...($forWebshop ? [] : [
                'price_discount' => $product->price_discount ? currency_format($product->price_discount) : null,
            ]),
        ];
    }

    /**
     * @return Builder
     */
    protected function fundsQuery(): Builder
    {
        return Fund::query();
    }

    /**
     * @param BaseFormRequest $request
     * @param Product $product
     * @return Collection
     */
    private function getProductFunds(BaseFormRequest $request, Product $product): Collection
    {
        $identityId = $request->auth_id();
        $fundsQuery = $this->productFundsQuery($product, $identityId);
        $fundsQuery->with([
            'organization', 'logo',
        ]);

        return $fundsQuery->get()->map(function (Fund $fund) use ($product, $identityId) {
            $fundProviderProduct = $product->getFundProviderProduct($fund);

            $data = [
                'id' => $fund->id,
                ...$fund->translateColumns($fund->only(['name'])),
                'logo' => new MediaResource($fund->logo),
                'organization' => $fund->organization->only([
                    'id', 'name',
                ]),
                'end_at' => $fund->end_date?->format('Y-m-d'),
                'end_at_locale' => format_date_locale($fund->end_date ?? null),
                ...$this->productFundFeatures($product, $fund, $fundProviderProduct),
            ];

            return [
                ...$data,
                'limit' => $this->productFundLimit($product, $fund, $fundProviderProduct, $identityId),
                'user_price' => new ProductUserPriceResource(
                    $product,
                    $this->fundPriceLabel($product, $fundProviderProduct),
                ),
            ];
        })->values();
    }
}
