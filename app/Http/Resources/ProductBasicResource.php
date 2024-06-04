<?php

namespace App\Http\Resources;

use App\Models\Product;

/**
 * @property Product $resource
 */
class ProductBasicResource extends ProductResource
{
    public const LOAD = [
        'organization',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return $this->baseFields($this->resource);
    }
}
