<?php

namespace App\Http\Resources;

use App\Http\Resources\Tiny\VoucherTinyResource;
use App\Models\FundRequest;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

/**
 * @property FundRequest $resource
 */
class FundRequestResource extends BaseJsonResource
{
    public const array LOAD = [
        'fund',
        'records',
        'vouchers',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $activeVouchers = $this->getActiveVouchers($this->resource);

        return [
            ...$this->resource->only([
                'id', 'state', 'employee_id', 'fund_id', 'contact_information',
            ]),
            'fund' => new FundResource($this->resource->fund),
            'records' => FundRequestRecordResource::collection($this->resource->records),
            'current_period' => $activeVouchers->isNotEmpty(),
            'active_vouchers' => VoucherTinyResource::collection($activeVouchers),
            ...$this->timestamps($this->resource, 'created_at', 'updated_at'),
        ];
    }

    /**
     * @param FundRequest $fundRequest
     * @return Voucher[]|Collection
     */
    public function getActiveVouchers(FundRequest $fundRequest): Collection|array
    {
        return $fundRequest->vouchers->filter(fn (Voucher $voucher) => !$voucher->expired);
    }
}
