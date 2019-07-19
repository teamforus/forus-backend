<?php

namespace App\Http\Resources\Sponsor;

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

        return array_merge(collect($voucher)->only([
            "id", 'amount', 'note',
        ])->toArray(), [
            'is_granted' => $voucher->is_granted,
            'address' => $address,
            'created_at' => $voucher->created_at->format('Y-m-d H:i:s'),
            'expire_at' => $voucher->updated_at->format('Y-m-d'),
            'created_at_locale' => format_datetime_locale($voucher->created_at),
            'expire_at_locale' => format_date_locale($voucher->expire_at),
            'fund' => $voucher->fund->only([
                'id', 'name', 'organization_id', 'state',
            ]),
        ]);
    }
}
