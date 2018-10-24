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

        if ($voucher->type == 'regular') {
            $amount = $voucher->amount_available;
            $offices = Office::getModel()->whereIn(
                'organization_id',
                $voucher->fund->providers->pluck('organization')
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

        $fundResource = collect($voucher->fund)->only([
            'id', 'name', 'state'
        ])->merge([
            'organization' => collect(
                $voucher->fund->organization
            )->only([
                'id', 'name'
            ])->merge([
                'logo' => new MediaCompactResource(
                    $voucher->fund->organization->logo
                )
            ]),
            'logo' => new MediaCompactResource($voucher->fund->logo),
            'product_categories' => ProductCategoryResource::collection(
                $voucher->fund->product_categories
            )
        ]);

        return collect($voucher)->only([
            'identity_address', 'fund_id', 'created_at_locale', 'address'
        ])->merge([
            'expire_at_locale' => $voucher->product ? format_date_locale($voucher->product->expire_at) : null,
            'timestamp' => $voucher->created_at->timestamp,
            'type' => $voucher->type,
            'offices' => OfficeResource::collection($offices),
            'product' => $productResource,
            'parent' => $voucher->parent ? collect($voucher->parent)->only([
                'identity_address', 'fund_id', 'created_at', 'address'
            ]) : null,
            'product_vouchers' => $voucher->product_vouchers ? collect(
                $voucher->product_vouchers
            )->map(function($product_voucher) {
                return collect($product_voucher)->only([
                    'identity_address', 'fund_id', 'created_at', 'address', 'amount'
                ])->merge([
                    'date' => $product_voucher->created_at->format('M d, Y'),
                    'date_time' => $product_voucher->created_at->format('M d, Y H:i'),
                    'timestamp' => $product_voucher->created_at->timestamp,
                    'product' => collect($product_voucher->product)->only([
                        'id', 'name', 'description', 'price', 'old_price',
                        'total_amount', 'sold_amount', 'product_category_id',
                        'organization_id'
                    ])
                ]);
            }) : null,
            'amount' => max($amount, 0),
            'fund' => $fundResource,
            'transactions' => VoucherTransactionResource::collection(
                $this->resource->transactions
            ),
        ])->toArray();
    }
}
