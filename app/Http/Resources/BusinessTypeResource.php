<?php

namespace App\Http\Resources;

use App\Models\BusinessType;

/**
 * @property BusinessType $resource
 */
class BusinessTypeResource extends BaseJsonResource
{
    public const LOAD = [
        'translations'
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|null
     */
    public function toArray($request): ?array
    {
        return $this->resource?->only('id', 'key', 'name');
    }
}
