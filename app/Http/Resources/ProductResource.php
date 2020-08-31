<?php

namespace App\Http\Resources;

use App\Models\Fund;
use App\Models\FundProviderProduct;
use App\Models\Product;
use App\Scopes\Builders\FundQuery;
use Illuminate\Http\Resources\Json\Resource;

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
        // 'organization.product_categories.translations',
        'organization.offices.photo.presets',
        'organization.offices.schedules',
        'organization.offices.organization',
        // 'organization.offices.organization.business_type.translations',
        'organization.offices.organization.logo.presets',
        // 'organization.offices.organization.product_categories.translations',
        // 'organization.supplied_funds_approved.logo',
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

        $funds = FundQuery::whereProductsAreApprovedFilter(FundQuery::whereActiveFilter(
            Fund::query()
        ), $product->id)->get();

        return array_merge($product->only([
            'id', 'name', 'description', 'product_category_id', 'sold_out',
            'organization_id'
        ]), [
            'description_html' => $product->description_html,
            'organization' => new OrganizationBasicResource($product->organization),
            'total_amount' => $product->total_amount,
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

            'funds' => $funds->map(static function(Fund $fund) use ($product) {
                /** @var FundProviderProduct $fundProviderProduct */
                $fundProviderProduct = $product->fund_provider_products()->whereHas('fund_provider.fund', static function(
                    \Illuminate\Database\Eloquent\Builder $builder
                ) use ($fund) {
                    $builder->where([
                        'fund_id' => $fund->id,
                        'type' => $fund::TYPE_SUBSIDIES,
                    ]);
                })->first();

                return [
                    'id' => $fund->id,
                    'name' => $fund->name,
                    'logo' => new MediaResource($fund->logo),
                    'type' => $fund->type,
                    'limit_total' => $fundProviderProduct->limit_total ?? null,
                    'limit_per_identity' => $fundProviderProduct->limit_per_identity ?? null,
                    'price' => $fund->isTypeSubsidy() ? $product->price - $fundProviderProduct->amount : $product->price,
                ];
            })->values(),


            // todo: optimize
            'price_min' => $product->price - $product->fund_provider_products()->where([
                'product_id' => $product->id,
            ])->whereHas('fund_provider.fund', static function(\Illuminate\Database\Eloquent\Builder $builder) {
                $builder->where('type', Fund::TYPE_SUBSIDIES);
            })->max('amount'),
            'price_max' => $product->price - $product->fund_provider_products()->where([
                'product_id' => $product->id,
            ])->whereHas('fund_provider.fund', static function(\Illuminate\Database\Eloquent\Builder $builder) {
                $builder->where('type', Fund::TYPE_SUBSIDIES);
            })->min('amount'),

            'photo' => new MediaResource($product->photo),
            'offices' => OfficeResource::collection($product->organization->offices),
            'product_category' => new ProductCategoryResource($product->product_category)
        ]);
    }
}
