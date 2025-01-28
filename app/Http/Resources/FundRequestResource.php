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
        return array_merge($this->resource->only([
            'id', 'state', 'employee_id', 'fund_id', 'contact_information',
        ]), [
            'fund' => new FundResource($this->resource->fund),
            'records' => FundRequestRecordResource::collection($this->resource->records),
            'current_period' => $this->activePeriod($this->resource),
        ], $this->timestamps($this->resource, 'created_at', 'updated_at'));
    }

    /**
     * @param FundRequest $fundRequest
     * @return bool
     */
    public function activePeriod(FundRequest $fundRequest): bool
    {
        return (bool)$fundRequest->vouchers->first(fn (Voucher $voucher) => !$voucher->expired);
    }
}
