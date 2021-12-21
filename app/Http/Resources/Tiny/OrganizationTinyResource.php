<?php

namespace App\Http\Resources\Tiny;

use App\Http\Resources\MediaResource;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Organization $resource
 */
class OrganizationTinyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return array_merge($this->resource->only("id", "name"), [
            'logo' => new MediaResource($this->resource->logo),
        ]);
    }
}
