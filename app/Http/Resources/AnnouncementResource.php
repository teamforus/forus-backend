<?php

namespace App\Http\Resources;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Throwable;

/**
 * @property Announcement $resource
 */
class AnnouncementResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @throws Throwable
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->only([
                'id', 'type', 'scope', 'dismissible',
            ]),
            ...$this->resource->translateColumns($this->resource->only([
                'title', 'description_html',
            ])),
        ];
    }
}
