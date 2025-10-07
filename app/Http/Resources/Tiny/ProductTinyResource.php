<?php

namespace App\Http\Resources\Tiny;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\MediaResource;
use Illuminate\Http\Request;
use App\Models\Product;

/**
 * @property-read Product $resource
 */
class ProductTinyResource extends BaseJsonResource
{
    public const array LOAD = [
        'photos.presets',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
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
