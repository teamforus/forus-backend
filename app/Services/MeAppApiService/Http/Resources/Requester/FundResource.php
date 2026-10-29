<?php

namespace App\Services\MeAppApiService\Http\Resources\Requester;

use App\Http\Resources\BaseJsonResource;
use App\Models\Fund;
use Illuminate\Http\Request;

/**
 * @property-read Fund $resource
 */
class FundResource extends BaseJsonResource
{
    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->only(['id']),
            ...$this->resource->translateColumns($this->resource->only(['name'])),
        ];
    }
}
