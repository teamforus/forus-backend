<?php

namespace App\Http\Resources;

use App\Models\Tag;
use Illuminate\Http\Request;

/**
 * @property Tag $resource
 */
class TagResource extends BaseJsonResource
{
    public const array LOAD = [
        'translations',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $tag = $this->resource;

        return $tag->only([
            'id', 'key', 'name',
        ]);
    }
}
