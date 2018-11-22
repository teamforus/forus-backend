<?php

namespace App\Http\Resources\Provider;

use App\Http\Resources\MediaCompactResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\ProductCategoryResource;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Http\Resources\Json\Resource;

class ProviderVoucherResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var Voucher $voucher */
        $voucher = $this->resource;
        $identityAddress = request()->get('identity');

        if ($voucher->product) {
            return $this->productVoucher($voucher);
        }

        return $this->regularVoucher($identityAddress, $voucher);
    }

    /**
     * Transform the resource into an array.
     *
     * @param string $identityAddress
     * @param Voucher $voucher
     * @return mixed
     */
    private function regularVoucher(
        string $identityAddress,
        Voucher $voucher
    ) {
        $amountLeft = $voucher->amount_available;
        $voucherOrganizations = $voucher->fund->providers->pluck('organization');

        $allowedOrganizations = Organization::queryByIdentityPermissions(
            $identityAddress, 'scan_vouchers'
        )->whereIn('id', $voucherOrganizations->pluck('id'))->get();

        $allowedProductCategories = $voucher->fund->product_categories;
        $allowedProducts = Product::getModel()->whereIn(
            'organization_id', $allowedOrganizations->pluck('id')
        )->where('sold_out', '=', false)->whereIn(
            'product_category_id', $allowedProductCategories->pluck('id')
        )->where('price', '<=', $amountLeft)->where(
            'expire_at', '>', date('Y-m-d')
        )->get();

        return collect($voucher)->only([
            'identity_address', 'fund_id', 'created_at', 'address'
        ])->merge([
            'type' => 'regular',
            'amount' => currency_format($amountLeft),
            'fund' => collect($voucher->fund)->only([
                'id', 'name', 'state'
            ])->merge([
                'organization' => collect($voucher->fund->organization)->only([
                    'id', 'name'
                ])->merge([
                    'logo' => new MediaCompactResource($voucher->fund->organization->logo)
                ]),
                'logo' => new MediaCompactResource($voucher->fund->logo)
            ]),
            'allowed_organizations' => collect(
                $allowedOrganizations
            )->map(function($organization) {
                return collect($organization)->only([
                    'id', 'name'
                ])->merge([
                    'logo' => new MediaCompactResource($organization->logo)
                ]);
            }),
            'allowed_product_categories' => ProductCategoryResource::collection(
                $allowedProductCategories
            ),
            'allowed_products' => collect($allowedProducts)->map(function($product) {
                /** @var Product $product */
                return collect($product)->only([
                    'id', 'name', 'description', 'total_amount', 'sold_amount'
                ])->merge([
                    'price' => currency_format($product->price),
                    'old_price' => currency_format($product->old_price),
                    'photo' => new MediaCompactResource($product->photo),
                    'product_category' => new ProductCategoryResource(
                        $product->product_category
                    )
                ]);
            }),
        ])->toArray();
    }

    /**
     * Transform the resource into an array.
     *
     * @param Voucher $voucher
     * @return mixed
     */
    private function productVoucher(
        Voucher $voucher
    ) {
        return collect($voucher)->only([
            'identity_address', 'fund_id', 'created_at', 'address'
        ])->merge([
            'type' => 'product',
            'product' => collect($voucher->product)->only([
                'id', 'name', 'description', 'total_amount', 'sold_amount',
                'product_category_id', 'organization_id'
            ])->merge([
                'price' => currency_format($voucher->product->price),
                'old_price' => currency_format($voucher->product->old_price),
                'photo' => new MediaResource(
                    $voucher->product->photo
                ),
                'organization' => collect($voucher->product->organization)->only([
                    'id', 'name'
                ])->merge([
                    'logo' => new MediaCompactResource($voucher->product->organization->logo)
                ]),
            ])->toArray()
        ])->toArray();
    }
}
