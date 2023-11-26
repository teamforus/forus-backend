<?php

namespace App\Http\Resources;

use App\Services\MollieService\Models\MollieConnectionProfile;

/**
 * @property MollieConnectionProfile $resource
 */
class MollieConnectionProfileResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|null
     */
    public function toArray($request): ?array
    {
        return $this->resource->only('id', 'name', 'email', 'phone', 'website');
    }
}
