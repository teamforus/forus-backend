<?php

namespace App\Http\Resources;

use App\Models\BusinessType;

/**
 * Class BusinessTypeResource
 * @property BusinessType $resource
 * @package App\Http\Resources
 */
class BusinessTypeResource extends BaseJsonResource
{
    public const LOAD = [
        'translations'
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request|any  $request
     * @return array|null
     */
    public function toArray($request): ?array
    {
        if ($this->resource === null) {
            return null;
        }

        return $this->resource->only('id', 'key', 'name');
    }
}
