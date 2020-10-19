<?php

namespace App\Http\Resources;

use App\Models\PhysicalCard;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class PhysicalCardResource
 * @property-read PhysicalCard $resource
 * @package App\Http\Resources
 */
class PhysicalCardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return $this->resource->only([
            'id', 'code'
        ]);
    }
}
