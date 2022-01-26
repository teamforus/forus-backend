<?php

namespace App\Http\Resources;

use App\Models\Tag;

/**
 * @property Tag $resource
 */
class TagResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return $this->resource->only([
            'id', 'name', 'key',
        ]);
    }
}
