<?php

namespace App\Http\Resources;

use App\Models\Announcement;
use Throwable;

/**
 * @property Announcement $resource
 */
class AnnouncementResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     * @throws Throwable
     */
    public function toArray($request): array
    {
        return $this->resource->only([
            'id', 'type', 'title', 'description_html', 'scope', 'dismissible',
        ]);
    }
}
