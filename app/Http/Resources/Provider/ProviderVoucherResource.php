<?php

namespace App\Http\Resources\Provider;

use App\Http\Resources\MediaCompactResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\OrganizationBasicResource;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Voucher;
use App\Scopes\Builders\ProductQuery;
use App\Scopes\Builders\VoucherQuery;
use Illuminate\Database\Eloquent\Builder;
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
        $productVouchers = null;
        $amountLeft = $voucher->amount_available;

        if ($voucher->type == 'regular') {
            $providersApproved = $voucher->fund->providers()->where([
                'allow_budget' => true,
            ])->pluck('organization_id')->toArray();
        } else {
            $providersApproved = $voucher->fund->providers()->where([
                'allow_products' => true,
            ])->orWhereHas('fund_provider_products', function(
                Builder $builder
            ) use ($voucher) {
                $builder->where('product_id', $voucher->product_id);
            })->pluck('organization_id')->toArray();
        }

        // list organizations allowed to scan vouchers to which
        // user have access with 'scan_vouchers' permission
        $allowedOrganizations = Organization::queryByIdentityPermissions(
            $identityAddress, 'scan_vouchers'
        )->whereIn('id', $providersApproved)->select([
            'id', 'name'
        ])->get()->map(function($organization) {
            return collect($organization)->merge([
                'logo' => new MediaCompactResource($organization->logo)
            ]);
        });

        if ($allowedOrganizations->count() == 1) {
            $productVouchers = $voucher->product_vouchers()->getQuery()->whereIn(
                'product_id',
                ProductQuery::approvedForFundsAndActiveFilter(Product::query()->whereIn(
                    'organization_id',
                    $allowedOrganizations->pluck('id')->toArray()
                ), $voucher->fund_id)->pluck('id')->toArray()
            )->whereHas('transactions', null, '=', 0)->get();

            $productVouchers = $productVouchers->map(function($product_voucher) {
                /** @var Voucher $product_voucher */
                return collect($product_voucher)->only([
                    'identity_address', 'fund_id', 'created_at',
                ])->merge([
                    'created_at_locale' => $product_voucher->created_at_locale,
                    'address' => $product_voucher->tokens->where(
                        'need_confirmation', 1)->first()->address,
                    'amount' => currency_format(
                        $product_voucher->type == 'regular' ?
                            $product_voucher->amount_available_cached :
                            $product_voucher->amount
                    ),
                    'date' => $product_voucher->created_at->format('M d, Y'),
                    'date_time' => $product_voucher->created_at->format('M d, Y H:i'),
                    'timestamp' => $product_voucher->created_at->timestamp,
                    'product' => collect($product_voucher->product)->only([
                        'id', 'name', 'description', 'total_amount',
                        'sold_amount', 'product_category_id', 'organization_id',
                    ])->merge([
                        'price' => currency_format($product_voucher->product->price),
                        'old_price' => currency_format($product_voucher->product->old_price),
                        'photo' => new MediaResource($product_voucher->product->photo),
                    ])
                ]);
            })->values();

            $productVouchers = $productVouchers->count() > 0 ? $productVouchers : null;
        }

        $fundData = collect($voucher->fund)->only([
            'id', 'name', 'state'
        ])->merge([
            'organization' => new OrganizationBasicResource(
                $voucher->fund->organization
            ),
            'logo' => new MediaCompactResource($voucher->fund->logo)
        ]);

        return collect($voucher)->only([
            'identity_address', 'fund_id', 'created_at', 'address'
        ])->merge([
            'type' => 'regular',
            'amount' => currency_format($amountLeft),
            'fund' => $fundData,
            'allowed_organizations' => $allowedOrganizations,
            // TODO: To be removed in next release
            'allowed_product_categories' => [],
            // TODO: To be removed in next release
            'allowed_products' => [],
            // TODO: To be moved to separate endpoint in next release
            'product_vouchers' => $productVouchers,
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
                'organization' => new OrganizationBasicResource(
                    $voucher->product->organization
                ),
            ])->toArray(),
        ])->toArray();
    }
}
