<?php

namespace App\Http\Resources;

use App\Models\ImplementationSocialMedia;
use Illuminate\Http\Request;

/**
 * @property-read ImplementationSocialMedia $resource
 */
class ImplementationSocialMediaResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return array_merge($this->resource->only([
            'id', 'url', 'type', 'type_locale', 'title',
        ]));
    }
}
