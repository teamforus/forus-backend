<?php

namespace App\Http\Resources\Tiny;

use App\Http\Resources\BaseJsonResource;
use App\Models\Voucher;
use Exception;
use Illuminate\Http\Request;

/**
 * @property Voucher $resource
 */
class VoucherTinyResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @throws Exception
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->only([
                'id', 'number', 'identity_id', 'fund_id', 'state', 'state_locale',
            ]),
            ...$this->makeTimestamps($this->resource->only([
                'created_at', 'expire_at',
            ])),
        ];
    }
}
