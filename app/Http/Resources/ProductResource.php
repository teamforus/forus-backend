<?php

namespace App\Http\Resources;

use App\Http\Resources\Traits\Products\FormatsProductPrices;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * @property Product $resource
 */
abstract class ProductResource extends BaseJsonResource
{
    use FormatsProductPrices;

    public const array LOAD = [
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

    protected const array SCANNING_AND_RESERVATION_FIELDS = [
        'qr_enabled', 'reservation_enabled', 'reservation_policy',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $product = $this->resource;

        return [
            ...$this->baseFields($product),
            'price_type' => $product->price_type,
            'expire_at' => $product->expire_at?->format('Y-m-d'),
            'expire_at_locale' => format_date_locale($product->expire_at ?? null),
            'expired' => $product->expired,
            'deleted_at' => $product->deleted_at?->format('Y-m-d'),
            'deleted_at_locale' => format_date_locale($product->deleted_at ?? null),
            'deleted' => $product->trashed(),
            'organization' => new OrganizationBasicResource($product->organization),
            'product_category' => $product->product_category
                ? new ProductCategoryResource($product->product_category)
                : null,
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
}
