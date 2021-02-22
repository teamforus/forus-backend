<?php

namespace App\Http\Resources;

use App\Models\Fund;
use App\Models\FundProviderProduct;
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
            'deleted' => !is_null($product->deleted_at),
            'funds' => $this->getProductFunds($product),
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
        )->with([
            'organization'
        ])->get()->map(function(Fund $fund) use ($product) {
            $fundProviderProduct = $fund->isTypeSubsidy() ? $product->getSubsidyDetailsForFund($fund) : null;

            return array_merge([
                'id' => $fund->id,
                'name' => $fund->name,
                'logo' => new MediaResource($fund->logo),
                'type' => $fund->type,
                'organization' => $fund->organization->only('id', 'name'),
                'end_at' => $fund->end_date ? $fund->end_date->format('Y-m-d') : null,
                'end_at_locale' => format_date_locale($fund->end_date ?? null),
            ], $fund->isTypeSubsidy() && $fundProviderProduct ? [
                'limit_total' => $fundProviderProduct->limit_total,
                'limit_total_unlimited' => $fundProviderProduct->limit_total_unlimited,
                'limit_per_identity' => $fundProviderProduct->limit_per_identity,
                'limit_available' => $this->getLimitAvailable($fundProviderProduct),
                'price' => currency_format($product->price - $fundProviderProduct->amount),
            ] : []);
        })->values();
    }

    /**
     * @param FundProviderProduct $providerProduct
     * @return int|null
     */
    private function getLimitAvailable(FundProviderProduct $providerProduct): ?int
    {
        if ($authAddress = auth_address()) {
            return $providerProduct->stockAvailableForIdentity($authAddress);
        }

        return !$providerProduct->limit_total_unlimited ? min(
            $providerProduct->limit_total,
            $providerProduct->limit_per_identity
        ) : $providerProduct->limit_per_identity;
    }

    /**
     * @param Product $product
     * @param string $type
     * @return float
     */
    private function getProductSubsidyPrice(Product $product, string $type): float {
        return max($product->price - $product->fund_provider_products()->where([
            'product_id' => $product->id,
        ])->whereHas('fund_provider.fund', function(Builder $builder) {
            $builder->where('funds.type', Fund::TYPE_SUBSIDIES);
            $builder->whereIn('funds.id', $this->fundsQuery()->pluck('id'));
        })->$type('amount'), 0);
    }
}
