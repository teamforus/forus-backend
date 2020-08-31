<?php

namespace App\Http\Resources;

use App\Models\PhysicalCardRequest;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class PhysicalCardRequestResource
 * @property PhysicalCardRequest $resource
 * @package App\Http\Resources
 */
class PhysicalCardRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return $this->resource->only([
            'id', 'address', 'house', 'house_addition', 'postcode', 'city',
        ]);
    }
}
