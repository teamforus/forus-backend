<?php

namespace App\Http\Resources;

use App\Models\ProductCategory;
use Illuminate\Http\Request;

/**
 * @property ProductCategory $resource
 */
class ProductCategoryResource extends BaseJsonResource
{
    public const array LOAD = [
        'translations',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return $this->resource->only([
            'id', 'key', 'name', 'parent_id',
        ]);
    }
}
