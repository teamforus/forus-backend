<?php

namespace App\Http\Resources\Tiny;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\MediaResource;
use App\Models\Organization;

/**
 * @property-read Organization $resource
 */
class OrganizationTinyResource extends BaseJsonResource
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
            "id", "name",
        ]), [
            'logo' => new MediaResource($this->resource->logo),
        ]);
    }
}
