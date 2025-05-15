<?php

namespace App\Http\Resources;

use App\Models\FundRequest;
use App\Models\Voucher;
use Illuminate\Http\Request;

/**
 * @property FundRequest $resource
 */
class FundRequestResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $hasActiveVoucher = $this->hasActiveVoucher($this->resource);

        return [
            ...$this->resource->only([
                'id', 'state', 'employee_id', 'fund_id', 'contact_information',
            ]),
            'fund' => new FundResource($this->resource->fund),
            'records' => FundRequestRecordResource::collection($this->resource->records),
            'current_period' => $hasActiveVoucher,
            'has_active_voucher' => $hasActiveVoucher,
            ...$this->timestamps($this->resource, 'created_at', 'updated_at'),
        ];
    }

    /**
     * @param FundRequest $fundRequest
     * @return bool
     */
    public function hasActiveVoucher(FundRequest $fundRequest): bool
    {
        return (bool) $fundRequest->vouchers->first(fn (Voucher $voucher) => !$voucher->expired);
    }
}
