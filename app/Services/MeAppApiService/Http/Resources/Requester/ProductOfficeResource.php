<?php

namespace App\Services\MeAppApiService\Http\Resources\Requester;

use App\Http\Resources\BaseJsonResource;
use App\Models\Office;
use Illuminate\Http\Request;

/**
 * @property-read Office $resource
 */
class ProductOfficeResource extends BaseJsonResource
{
    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return $this->resource->only([
            'id', 'organization_id', 'address', 'phone', 'lat', 'lon',
        ]);
    }
}
