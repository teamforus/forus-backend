<?php

namespace App\Modules\VoucherPass\Http\Resources;

use App\Http\Resources\BaseJsonResource;
use App\Modules\VoucherPass\Models\VoucherPassType;
use Illuminate\Http\Request;

/**
 * @property-read VoucherPassType $resource
 */
class VoucherPassTypeResource extends BaseJsonResource
{
    public const array LOAD = ['funds'];

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->only([
                'id', 'organization_id', 'name',
            ]),
            'fund_ids' => $this->resource->funds->modelKeys(),
        ];
    }
}
