<?php

namespace App\Services\MeAppApiService\Http\Resources\Requester;

use App\Models\Fund;
use App\Services\MeAppApiService\Http\Resources\MobileBaseJsonResource;
use Illuminate\Http\Request;

/**
 * @property-read Fund $resource
 */
class FundResource extends MobileBaseJsonResource
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
