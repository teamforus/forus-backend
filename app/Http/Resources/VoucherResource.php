<?php

namespace App\Http\Resources;

use App\Models\Office;
use App\Models\Organization;
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
        /**
         * @var Voucher $voucher
         */
        $voucher = $this->resource;
        $amountLeft = $voucher->amount - $voucher->transactions->sum('amount');



        $voucherOrganizations = $voucher->fund->providers->pluck('organization');

        $offices = Office::getModel()->whereIn(
            'organization_id', $voucherOrganizations
        )->get();

        return collect($voucher)->only([
            'identity_address', 'fund_id', 'created_at', 'address'
        ])->merge([
            'offices' => OfficeResource::collection($offices),
            'amount' => max($amountLeft, 0),
            'fund' => collect($voucher->fund)->only([
                'id', 'name', 'state'
            ])->merge([
                'organization' => collect($voucher->fund->organization)->only([
                    'id', 'name'
                ])->merge([
                    'logo' => new MediaCompactResource($voucher->fund->organization->logo)
                ]),
                'logo' => new MediaCompactResource($voucher->fund->logo),
                'product_categories' => ProductCategoryResource::collection(
                    $voucher->fund->product_categories
                )
            ]),
            'transactions' => VoucherTransactionResource::collection(
                $this->resource->transactions
            ),
        ])->toArray();
    }
}
