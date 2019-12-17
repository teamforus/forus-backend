<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\MediaResource;
use App\Http\Resources\OrganizationBasicResource;
use App\Models\Voucher;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class SponsorVoucherResource
 * @property Voucher $resource
 * @package App\Http\Resources\Sponsor
 */
class SponsorVoucherResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $voucher = $this->resource;
        $address = $voucher->is_granted ? null :
            $voucher->token_without_confirmation->address ?? null;

        if ($voucher->type == 'regular') {
            $productResource = null;
        } elseif ($voucher->type == 'product') {
            $productResource = collect($voucher->product)->only([
                'id', 'name', 'description', 'price', 'old_price',
                'total_amount', 'sold_amount', 'product_category_id',
                'organization_id'
            ])->merge([
                'product_category' => $voucher->product->product_category,
                'expire_at' => $voucher->product->expire_at->format('Y-m-d'),
                'expire_at_locale' => format_datetime_locale($voucher->product->expire_at),
                'photo' => new MediaResource($voucher->product->photo),
                'organization' => new OrganizationBasicResource(
                    $voucher->product->organization
                ),
            ])->toArray();
        } else {
            exit(abort("Unknown voucher type!", 403));
        }

        return array_merge(collect($voucher)->only([
            'id', 'amount', 'note'
        ])->toArray(), [
            'is_granted' => $voucher->is_granted,
            'identity_email' => resolve('forus.services.record')->primaryEmailByAddress(
                $voucher->identity_address
            ),
            'has_transactions' => $voucher->has_transactions,
            'address' => $address,
            'created_at' => $voucher->created_at->format('Y-m-d H:i:s'),
            'expire_at' => $voucher->updated_at->format('Y-m-d'),
            'created_at_locale' => format_datetime_locale($voucher->created_at),
            'expire_at_locale' => format_date_locale($voucher->expire_at),
            'fund' => $voucher->fund->only([
                'id', 'name', 'organization_id', 'state',
            ]),
            'product' => $productResource,
        ]);
    }
}
