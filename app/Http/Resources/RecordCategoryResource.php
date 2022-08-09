<?php

namespace App\Http\Resources;

use App\Models\RecordCategory;

/**
 * @property-read RecordCategory $resource
 */
class RecordCategoryResource extends BaseJsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return $this->resource->only('id', 'name', 'order');
    }
}
