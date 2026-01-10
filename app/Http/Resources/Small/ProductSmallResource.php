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
    public const array LOAD = [
    ];

    public const array LOAD_NESTED = [
        'photos' => MediaResource::class,
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $product = $this->resource;

        return [
            ...$this->baseFields($product),
            'photo' => new MediaResource($product->photos[0] ?? null),
            'photos' => MediaResource::collection($product->photos),
        ];
    }
}
