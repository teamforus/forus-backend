<?php

namespace App\Http\Resources\Tiny;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\MediaResource;
use App\Models\Product;

/**
 * @property-read Product $resource
 */
class ProductTinyResource extends BaseJsonResource
{
    public const LOAD = [
        'photo.presets',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return array_merge($this->resource->only([
            "id", "name",
        ]), [
            'photo' => new MediaResource($this->resource->photo),
        ]);
    }
}
