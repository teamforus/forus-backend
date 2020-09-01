<?php

namespace App\Http\Resources;

use App\Models\BusinessType;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class BusinessTypeResource
 * @property BusinessType $resource
 * @package App\Http\Resources
 */
class BusinessTypeResource extends Resource
{
    public static $load = [
        'translations'
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request|any  $request
     * @return array
     */
    public function toArray($request): array
    {
        return $this->resource->only([
            'id', 'key', 'name'
        ]);
    }
}
