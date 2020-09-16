<?php

namespace App\Http\Resources;

use App\Models\Fund;
use App\Models\Product;
use App\Scopes\Builders\FundQuery;
use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class ProductResource
 * @property Product $resource
 * @package App\Http\Resources
 */
class ProductResource extends Resource
{
    public static $load = [
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
            'id', 'name', 'description', 'product_category_id', 'sold_out', 'organization_id',
        ]), [
            'description_html' => $product->description_html,
            'organization' => new OrganizationBasicResource($product->organization),
            'total_amount' => $product->total_amount,
            'no_price' => $product->no_price,
            'unlimited_stock' => $product->unlimited_stock,
            'reserved_amount' => $product->vouchers_reserved->count(),
            'sold_amount' => $product->countSold(),
            'stock_amount' => $product->stock_amount,
            'price' => currency_format($product->price),
            'old_price' => $product->old_price ? currency_format($product->old_price) : null,
            'expire_at' => $product->expire_at->format('Y-m-d'),
            'expire_at_locale' => format_date_locale($product->expire_at ?? null),
            'expired' => $product->expired,
            'deleted_at' => $product->deleted_at ? $product->deleted_at->format('Y-m-d') : null,
            'deleted_at_locale' => format_date_locale($product->deleted_at ?? null),
            'deleted' => !is_null($product->deleted_at),
            'funds' => $this->getProductFunds($product),
            'price_min' => currency_format($this->getProductSubsidyPrice($product, 'min')),
            'price_max' => currency_format($this->getProductSubsidyPrice($product, 'max')),
            'photo' => new MediaResource($product->photo),
            'offices' => OfficeResource::collection($product->organization->offices),
            'product_category' => new ProductCategoryResource($product->product_category)
        ]);
    }

    /**
     * @return Builder
     */
    protected function fundsQuery(): Builder {
        return Fund::query();
    }

    /**
     * @param Product $product
     * @return Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
     */
    private function getProductFunds(Product $product) {
        return FundQuery::whereProductsAreApprovedAndActiveFilter(
            $this->fundsQuery(), $product->id
        )->get()->map(static function(Fund $fund) use ($product) {
            $fundProviderProduct = $fund->isTypeSubsidy() ?
                $product->getSubsidyDetailsForFund($fund) : null;

            return array_merge([
                'id' => $fund->id,
                'name' => $fund->name,
                'logo' => new MediaResource($fund->logo),
                'type' => $fund->type,
            ], $fund->isTypeSubsidy() && $fundProviderProduct ? [
                'limit_total' => $fundProviderProduct->limit_total,
                'limit_per_identity' => $fundProviderProduct->limit_per_identity,
                'limit_available' => !auth_address() ? min(
                    $fundProviderProduct->limit_total,
                    $fundProviderProduct->limit_per_identity
                ): $fundProviderProduct->stockAvailableForIdentity(auth_address()),
                'price' => $product->price - $fundProviderProduct->amount,
                'approved'  => FundQuery::whereProductsAreApprovedFilter(
                    Fund::query()->whereId($fund->id),
                    $product->id
                )->exists()
            ] : []);
        })->values();
    }

    /**
     * @param Product $product
     * @param string $type
     * @return float
     */
    private function getProductSubsidyPrice(Product $product, $type = 'min'): float {
        return $product->price - $product->fund_provider_products()->where([
            'product_id' => $product->id,
        ])->whereHas('fund_provider.fund', static function(Builder $builder) {
            $builder->where('type', Fund::TYPE_SUBSIDIES);
        })->$type('amount');
    }
}
