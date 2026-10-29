<?php

namespace App\Services\MeAppApiService\Http\Resources\Shared;

use App\Http\Resources\BaseJsonResource;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

/**
 * @property-read ProductCategory $resource
 */
class ProductCategoryResource extends BaseJsonResource
{
    public const array LOAD = [
        'translations',
    ];

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->only(['id', 'key']),
            'name' => $this->resource->name,
            'parent_id' => $this->resource->parent_id,
        ];
    }
}
