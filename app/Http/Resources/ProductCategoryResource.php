<?php

namespace App\Http\Resources;

use App\Models\ProductCategory;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class ProductCategoryResource
 * @property ProductCategory $resource
 * @package App\Http\Resources
 */
class ProductCategoryResource extends Resource
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
            'id', 'key', 'name', 'parent_id',
        ]);
    }
}
