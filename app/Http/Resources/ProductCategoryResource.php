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
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return collect($this->resource)->only([
            'id', 'key', 'name', 'service'
        ])->toArray();
    }
}
