<?php

namespace App\Http\Resources;

use App\Models\Tag;
use Illuminate\Http\Request;

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
    public function toArray(Request $request): array
    {
        $tag = $this->resource;

        return [
            'id' => $tag->id,
            'key' => $tag->key,
            ...$tag->translateColumns($tag->only([
                'name',
            ])),
        ];
    }
}
