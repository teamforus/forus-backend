<?php

namespace App\Http\Resources;

use App\Models\PhysicalCard;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read PhysicalCard $resource
 */
class PhysicalCardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return $this->resource->only([
            'id', 'code',
        ]);
    }
}
