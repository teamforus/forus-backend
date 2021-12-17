<?php

namespace App\Http\Resources\Tiny;

use App\Http\Resources\MediaResource;
use App\Models\Fund;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Fund $resource
 */
class FundTinyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return array_merge($this->resource->only("id", "name", "organization_id"), [
            'logo' => new MediaResource($this->resource->logo),
        ]);
    }
}
