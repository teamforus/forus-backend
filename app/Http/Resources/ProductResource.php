<?php

namespace App\Http\Resources;

use App\Models\Fund;
use App\Models\Product;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\ProductSubQuery;
use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class ProductResource
 * @property Product $resource
 * @package App\Http\Resources
 */
class ProductResource extends Resource
{
    public static function load(): array
    {
        return [
            'voucher_transactions',
            'vouchers_reserved',
            'photo.presets',
            'product_category.translations',
            'organization.offices.photo.presets',
            'organization.offices.schedules',
            'organization.offices.organization',
            'organization.offices.organization.logo.presets',
            'organization.logo.presets',
            'organization.business_type.translations',
        ];
    }

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request|any $request
     * @return array
     */
    public function toArray($request): array
    {
        $product = $this->resource;

        return array_merge($product->only([
            'id', 'name', 'description', 'description_html', 'product_category_id', 'sold_out',
            'organization_id', 'reservation_enabled', 'reservation_policy',
        ]), [
            'organization' => new OrganizationBasicResource($product->organization),
            'total_amount' => $product->total_amount,

            // new price fields
            'price_type' => $product->price_type,
            'price_discount' => $product->price_discount ? currency_format($product->price_discount) : null,
            'price_discount_locale' => $product->price_discount_locale,

            'unlimited_stock' => $product->unlimited_stock,
            'reserved_amount' => $product->vouchers_reserved->count(),
            'sold_amount' => $product->countSold(),
            'stock_amount' => $product->stock_amount,
            'price' => is_null($product->price) ? null : currency_format($product->price),
            'price_locale' => $product->price_locale,
            'expire_at' => $product->expire_at ? $product->expire_at->format('Y-m-d') : null,
            'expire_at_locale' => format_date_locale($product->expire_at ?? null),
            'expired' => $product->expired,
            'deleted_at' => $product->deleted_at ? $product->deleted_at->format('Y-m-d') : null,
            'deleted_at_locale' => format_date_locale($product->deleted_at ?? null),
            'deleted' => $product->trashed(),
            'funds' => $product->trashed() ? [] : $this->getProductFunds($product),
            'price_min' => currency_format($this->getProductSubsidyPrice($product, 'max')),
            'price_max' => currency_format($this->getProductSubsidyPrice($product, 'min')),
            'photo' => new MediaResource($product->photo),
            'offices' => OfficeResource::collection($product->organization->offices),
            'product_category' => new ProductCategoryResource($product->product_category)
        ]);
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
     * @return Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
     */
    private function getProductFunds(Product $product) {
        $fundsQuery = FundQuery::whereProductsAreApprovedAndActiveFilter($this->fundsQuery(), $product);
        $fundsQuery->with('organization');

        return $fundsQuery->get()->map(function(Fund $fund) use ($product) {
            $data = [
                'id' => $fund->id,
                'name' => $fund->name,
                'logo' => new MediaResource($fund->logo),
                'type' => $fund->type,
                'organization' => $fund->organization->only('id', 'name'),
                'end_at' => $fund->end_date ? $fund->end_date->format('Y-m-d') : null,
                'end_at_locale' => format_date_locale($fund->end_date ?? null),
                'reservations_enabled' => $product->reservationsEnabled($fund),
            ];

            if (!$fund->isTypeSubsidy()) {
                return $data;
            }

            $fundProviderProduct = $product->getSubsidyDetailsForFund($fund);
            $productData = ProductSubQuery::appendReservationStats([
                'identity_address' => auth_address(),
                'fund_id' => $fund->id
            ], Product::whereId($product->id))->first()->only([
                'limit_total', 'limit_per_identity', 'limit_available'
            ]);

            return array_merge($data, $productData, [
                'price' => $fundProviderProduct->user_price,
                'price_locale' => $fundProviderProduct->user_price_locale,
                'limit_per_identity' => $fundProviderProduct->limit_per_identity,
            ]);
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
        ])->whereHas('fund_provider.fund', function(Builder $builder) {
            $builder->where('funds.type', Fund::TYPE_SUBSIDIES);
            $builder->whereIn('funds.id', $this->fundsQuery()->select('funds.id'));
        })->$type('amount'), 0);
    }
}
