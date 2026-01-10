<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;

/**
 * @property Product $resource
 */
class ProductBasicResource extends ProductResource
{
    public const array LOAD = [
        'organization',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return $this->baseFields($this->resource);
    }
}
