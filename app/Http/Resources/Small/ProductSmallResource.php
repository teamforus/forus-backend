<?php

namespace App\Http\Resources\Small;

use App\Http\Resources\MediaResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * @property Product $resource
 */
class ProductSmallResource extends ProductResource
{
    public const array LOAD = [];

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $product = $this->resource;

        return [
            ...$this->baseFields($product),
            'photo' => new MediaResource($product->photo),
        ];
    }
}
