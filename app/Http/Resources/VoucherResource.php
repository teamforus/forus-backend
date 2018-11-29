<?php

namespace App\Http\Resources;

use App\Models\Office;
use App\Models\Voucher;
use Illuminate\Http\Resources\Json\Resource;

class VoucherResource extends Resource
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
        $fund = $voucher->fund;

        if ($voucher->type == 'regular') {
            $amount = $voucher->amount_available;
            $offices = Office::getModel()->whereIn(
                'organization_id',
                $fund->providers->pluck('organization')
            )->get();

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
                'expire_at_locale' => format_date_locale($voucher->product->expire_at),
                'photo' => new MediaResource(
                    $voucher->product->photo
                ),
                'organization' => collect(
                    $voucher->product->organization
                )->only([
                    'id', 'name'
                ])->merge([
                    'logo' => new MediaCompactResource(
                        $voucher->product->organization->logo
                    )
                ]),
            ])->toArray();
        } else {
            exit(abort("Unknown voucher type!", 403));
        }

        $urlWebshop = null;

        if ($fund->fund_config && 
            $fund->fund_config->implementation) {
            $urlWebshop = $fund->fund_config->implementation->url_webshop;
        }

        $fundResource = collect($fund)->only([
            'id', 'name', 'state'
        ])->merge([
            'url_webshop' => $urlWebshop,
            'organization' => collect(
                $fund->organization
            )->only([
                'id', 'name'
            ])->merge([
                'logo' => new MediaCompactResource($fund->organization->logo)
            ]),
            'logo' => new MediaCompactResource($fund->logo),
            'product_categories' => ProductCategoryResource::collection(
                $fund->product_categories
            )
        ]);

        return collect($voucher)->only([
            'identity_address', 'fund_id', 'created_at', 'created_at_locale',
        ])->merge([
            'amount' => currency_format($amount),
            'address' => $voucher->tokens()->where('need_confirmation', 1)->first()->address,
            'address_printable' => $voucher->tokens()->where('need_confirmation', 0)->first()->address,
            'expire_at' => $voucher->product ? $voucher->product->expire_at->format('Y-m-d') : null,
            'expire_at_locale' => $voucher->product ? format_date_locale($voucher->product->expire_at) : null,
            'timestamp' => $voucher->created_at->timestamp,
            'type' => $voucher->type,
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
                    'identity_address', 'fund_id', 'created_at', 'created_at_locale'
                ])->merge([
                    'amount' => currency_format(
                        $product_voucher->type == 'regular' ? $product_voucher->amount_available : $product_voucher->amount
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
            }) : null,
            'fund' => $fundResource,
            'transactions' => VoucherTransactionResource::collection(
                $this->resource->transactions
            ),
        ])->toArray();
    }
}
