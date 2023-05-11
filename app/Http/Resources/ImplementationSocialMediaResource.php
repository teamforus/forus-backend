<?php

namespace App\Http\Resources;

use App\Models\ImplementationSocialMedia;

/**
 * @property-read ImplementationSocialMedia $resource
 */
class ImplementationSocialMediaResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return array_merge($this->resource->only([
            'id', 'url', 'type', 'type_locale', 'title',
        ]));
    }
}
