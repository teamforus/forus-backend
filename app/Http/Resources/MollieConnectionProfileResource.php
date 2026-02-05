<?php

namespace App\Http\Resources;

use App\Services\MollieService\Models\MollieConnectionProfile;
use Illuminate\Http\Request;

/**
 * @property MollieConnectionProfile $resource
 */
class MollieConnectionProfileResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|null
     */
    public function toArray(Request $request): ?array
    {
        return $this->resource->only('id', 'name', 'email', 'phone', 'website', 'current');
    }
}
