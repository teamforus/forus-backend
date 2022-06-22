<?php

namespace App\Http\Resources;

use App\Models\Announcement;
use Throwable;

/**
 * Class AnnouncementResource
 * @property Announcement $resource
 * @package App\Http\Resources
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
            'id', 'type', 'title', 'description', 'expire_at', 'scope', 'active', 'description_html',
        ]);
    }
}
