<?php

namespace App\Http\Resources;

use App\Models\PhysicalCardRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property PhysicalCardRequest $resource
 */
class PhysicalCardRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return $this->resource->only([
            'id', 'address', 'house', 'house_addition', 'postcode', 'city',
        ]);
    }
}
