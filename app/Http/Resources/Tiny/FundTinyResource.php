<?php

namespace App\Http\Resources\Tiny;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\MediaResource;
use App\Models\Fund;

/**
 * @property-read Fund $resource
 */
class FundTinyResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return array_merge($this->resource->only([
            "id", "name", "organization_id",
        ]), [
            'logo' => new MediaResource($this->resource->logo),
            'organization_name' => $this->resource->organization->name,
        ]);
    }
}
