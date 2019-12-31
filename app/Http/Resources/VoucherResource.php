<?php

namespace App\Http\Resources;

use App\Models\Voucher;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class VoucherResource
 * @property Voucher $resource
 * @package App\Http\Resources
 */
class VoucherResource extends Resource
{
    /**
     * @var array
     */
    public static $load = [
        'parent',
        'tokens',
        'transactions.voucher.fund.logo.sizes',
        'transactions.provider.logo.sizes',
        'transactions.product.photo.sizes',
        'product_vouchers.product.photo.sizes',
        'product.photo.sizes',
        'product.product_category.translations',
        'product.organization.logo.sizes',
        'product.organization.offices.schedules',
        'product.organization.offices.photo.sizes',
        'product.organization.offices.organization.logo.sizes',
        // 'product.organization.offices.organization.product_categories.translations',
        // 'product.organization.product_categories.translations',
        'fund.fund_config.implementation',
        'fund.product_categories.translations',
        'fund.provider_organizations_approved.offices.schedules',
        'fund.provider_organizations_approved.offices.photo.sizes',
        'fund.provider_organizations_approved.offices.organization.logo.sizes',
        // 'fund.provider_organizations_approved.offices.organization.product_categories.translations',
        'fund.logo.sizes',
        'fund.organization.logo.sizes',
        // 'fund.organization.product_categories.translations',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $voucher = $this->resource;
        $fund = $this->resource->fund;

        if ($voucher->type == 'regular') {
            $amount = $voucher->amount_available_cached;
            $offices = $voucher->fund->provider_organizations_approved->pluck(
                'offices'
            )->flatten();
            $productResource = null;
        } elseif ($voucher->type == 'product') {
            $amount = $voucher->amount;
            $offices = $voucher->product->organization->offices;
            $productResource = collect($voucher->product)->only([
                'id', 'name', 'description', 'price', 'old_price',
                'total_amount', 'sold_amount', 'product_category_id',
                'organization_id'
            ])->merge([
                'product_category' => $voucher->product->product_category,
                'expire_at' => $voucher->product->expire_at->format('Y-m-d'),
                'expire_at_locale' => format_datetime_locale($voucher->product->expire_at),
                'photo' => new MediaResource($voucher->product->photo),
                'organization' => new OrganizationBasicWithPrivateResource(
                    $voucher->product->organization
                ),
            ])->toArray();
        } else {
            exit(abort("Unknown voucher type!", 403));
        }

        $urlWebShop = null;

        if ($fund->fund_config &&
            $fund->fund_config->implementation) {
            $urlWebShop = $fund->fund_config->implementation->url_webshop;
        }

        $fundResource = collect($fund)->only([
            'id', 'name', 'state'
        ])->merge([
            'url_webshop' => $urlWebShop,
            'logo' => new MediaCompactResource($fund->logo),
            'start_date' => $fund->start_date->format('Y-m-d H:i'),
            'start_date_locale' => format_datetime_locale($fund->start_date),
            'end_date' => $fund->end_date->format('Y-m-d H:i'),
            'end_date_locale' => format_date_locale($fund->end_date),
            'organization' => new OrganizationBasicWithPrivateResource(
                $fund->organization
            ),
            'product_categories' => ProductCategoryResource::collection(
                $fund->product_categories
            ),
        ]);

        $transactions = VoucherTransactionResource::collection(
            $voucher->transactions
        );

        return collect($voucher)->only([
            'identity_address', 'fund_id', 'created_at', 'returnable'
        ])->merge([
            'expire_at' => $voucher->expire_at,
            'expire_at_locale' => format_date_locale($voucher->expire_at),
            'expired' => $voucher->expired,
            'created_at_locale' => $voucher->created_at_locale,
            'amount' => currency_format($amount),
            'address' => $voucher->tokens->where('need_confirmation', 1)->first()->address,
            'address_printable' => $voucher->tokens->where('need_confirmation', 0)->first()->address,
            'timestamp' => $voucher->created_at->timestamp,
            'type' => $voucher->type,
            'fund' => $fundResource,
            'offices' => OfficeResource::collection($offices),
            'product' => $productResource,
            'parent' => $voucher->parent ? collect($voucher->parent)->only([
                'identity_address', 'fund_id', 'created_at'
            ]) : null,
            'product_vouchers' => $voucher->product_vouchers ? collect(
                $voucher->product_vouchers
            )->map(function($product_voucher) {
                /** @var Voucher $product_voucher */
                return collect($product_voucher)->only([
                    'identity_address', 'fund_id', 'created_at', 'returnable',
                ])->merge([
                    'created_at_locale' => $product_voucher->created_at_locale,
                    'address' => $product_voucher->tokens->where(
                        'need_confirmation', 1)->first()->address,
                    'amount' => currency_format(
                        $product_voucher->type == 'regular' ? $product_voucher->amount_available_cached : $product_voucher->amount
                    ),
                    'date' => $product_voucher->created_at->format('M d, Y'),
                    'date_time' => $product_voucher->created_at->format('M d, Y H:i'),
                    'timestamp' => $product_voucher->created_at->timestamp,
                    'product' => collect($product_voucher->product)->only([
                        'id', 'name', 'description', 'total_amount',
                        'sold_amount', 'product_category_id', 'organization_id'
                    ])->merge([
                        'price' => currency_format($product_voucher->product->price),
                        'old_price' => currency_format($product_voucher->product->old_price),
                    ])
                ]);
            })->values() : null,
            'transactions' => $transactions,
        ])->toArray();
    }
}
