<?php

namespace App\Http\Resources;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\FundProviderProduct;
use App\Models\Organization;
use App\Models\Product;
use App\Scopes\Builders\FundQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;

/**
 * @property FundProviderProduct $resource
 * @property Organization $organization
 */
class FundProviderProductDigestResource extends BaseJsonResource
{
    public const LOAD = [
        'product.logs',
        'product.photo.presets',
        'product.product_category.translations',
        'product.organization.offices.photo.presets',
        'product.organization.offices.schedules',
        'product.organization.offices.organization',
        'product.organization.offices.organization.logo.presets',
        'product.organization.logo.presets',
        'fund_provider.fund',
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

        $fundProviderProduct = $this->resource;
        $product = $fundProviderProduct->product;

        $lastDigestLog = $product->logs()->where(
            'event', Product::EVENT_UPDATED_DIGEST
        )->orderByDesc('event_logs.created_at')->first();

        $updatedAt = $lastDigestLog ? $lastDigestLog->created_at : $product->created_at;

        return array_merge($this->baseFields($product), [
            'fund_provider_id' => $fundProviderProduct->fund_provider_id,
            'fund_id' => $fundProviderProduct->fund_provider->fund->id,
            'fund' => new FundResource($fundProviderProduct->fund_provider->fund),
            'photo' => new MediaResource($product->photo),
            'organization' => new OrganizationBasicResource($product->organization),
            'description_html' => $product->description_html,
            'total_amount' => $product->total_amount,
            'unlimited_stock' => $product->unlimited_stock,
            'reserved_amount' => $product->countReservedCached(),
            'sold_amount' => $product->countSold(),
            'stock_amount' => $product->stock_amount,
            'expire_at' => $product->expire_at?->format('Y-m-d'),
            'expire_at_locale' => format_date_locale($product->expire_at ?? null),
            'expired' => $product->expired,
            'deleted_at' => $product->deleted_at?->format('Y-m-d'),
            'deleted_at_locale' => format_date_locale($product->deleted_at ?? null),
            'deleted' => $product->trashed(),
            'funds' => $product->trashed() ? [] : $this->getProductFunds($product),
            'offices' => OfficeResource::collection($product->organization->offices),
            'product_category' => new ProductCategoryResource($product->product_category),
            'bookmarked' => $product->isBookmarkedBy($baseRequest->identity()),
            'digest_logs_count' => $product->logs()->where('event', Product::EVENT_UPDATED_DIGEST)->count(),
            'updated_at' => $updatedAt,
            'updated_at_locale' => format_datetime_locale($updatedAt),
            'created_at' => $product->created_at,
            'created_at_locale' => format_datetime_locale($product->created_at),
        ], array_merge(
            $this->priceFields($product),
        ));
    }

    /**
     * @param Product $product
     * @return array
     */
    protected function baseFields(Product $product): array
    {
        return array_merge($product->only([
            'id', 'name', 'description', 'product_category_id', 'sold_out',
            'organization_id', 'alternative_text',
        ]), [
            'price' => is_null($product->price) ? null : currency_format($product->price),
            'price_locale' => $product->price_locale,
            'organization' => $product->organization->only('id', 'name'),
        ]);
    }

    /**
     * @param Product $product
     * @return array
     */
    protected function priceFields(Product $product): array {
        return [
            'price_type' => $product->price_type,
            'price_discount' => $product->price_discount ? currency_format($product->price_discount) : null,
            'price_discount_locale' => $product->price_discount_locale,
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
     * @param Product $product
     * @return Collection
     */
    private function getProductFunds(Product $product): Collection
    {
        $fundsQuery = FundQuery::whereProductsAreApprovedAndActiveFilter($this->fundsQuery(), $product);
        $fundsQuery->with([
            'organization', 'logo',
        ]);

        return $fundsQuery->get()->map(function(Fund $fund) {
            return [
                'id' => $fund->id,
                'name' => $fund->name,
                'logo' => new MediaResource($fund->logo),
                'type' => $fund->type,
                'organization' => $fund->organization->only('id', 'name'),
                'end_at' => $fund->end_date?->format('Y-m-d'),
                'end_at_locale' => format_date_locale($fund->end_date ?? null),
            ];
        })->values();
    }
}
