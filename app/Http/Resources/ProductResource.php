<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Resources\Json\Resource;

class ProductResource extends Resource
{
    public static $load = [
        'voucher_transactions',
        'vouchers_reserved',
        'photo.sizes',
        'product_category.translations',
        'product_category.funds',
        'organization.product_categories.translations',
        'organization.offices.photo.sizes',
        'organization.offices.schedules',
        'organization.offices.organization',
        'organization.offices.organization.logo.sizes',
        'organization.offices.organization.product_categories.translations',
        'organization.supplied_funds_approved',
        'organization.logo.sizes',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var Product $product */
        $product = $this->resource;
        $suppliedFundIds = $product->organization->supplied_funds_approved;

        $funds = $product->product_category->funds->whereIn(
            'id', $suppliedFundIds->pluck('id')
        );

        $totalAmount = $product->total_amount;
        $countReserved = $product->vouchers_reserved->count();
        $countSold = $product->voucher_transactions->count();

        return collect($product)->only([
            'id', 'name', 'description', 'product_category_id', 'sold_out',
            'organization_id'
        ])->merge([
            'organization' => new OrganizationBasicResource(
                $product->organization
            ),
            'total_amount' => $totalAmount,
            'reserved_amount' => $countReserved,
            'stock_amount' => $totalAmount - ($countReserved + $countSold),
            'price' => currency_format($product->price),
            'old_price' => currency_format($product->old_price),
            'expire_at' => $product->expire_at->format('Y-m-d'),
            'expire_at_locale' => format_date_locale($product->expire_at),
            'expired' => $product->expired,
            'deleted_at' => $product->deleted_at ? $product->deleted_at->format('Y-m-d') : null,
            'deleted_at_locale' => $product->deleted_at ? format_date_locale($product->deleted_at) : null,
            'deleted' => !is_null($product->deleted_at),
            'funds' => $funds->map(function($fund) {
                return [
                    'logo' => new MediaResource($fund->logo),
                    'id' => $fund->id,
                    'name' => $fund->name
                ];
            }),
            'offices' => OfficeResource::collection(
                $product->organization->offices
            ),
            'photo' => new MediaResource(
                $product->photo
            ),
            'product_category' => new ProductCategoryResource(
                $product->product_category
            )
        ])->toArray();
    }
}
