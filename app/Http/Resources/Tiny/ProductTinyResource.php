<?php

namespace App\Http\Resources\Tiny;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\MediaResource;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * @property-read Product $resource
 */
class ProductTinyResource extends BaseJsonResource
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
        return array_merge($this->resource->only([
            'id', 'name',
        ]), [
            'photos' => MediaResource::collection($this->resource->photos),
        ]);
    }
}
