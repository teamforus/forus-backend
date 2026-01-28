<?php

namespace App\Http\Resources;

use App\Models\RecordCategory;
use Illuminate\Http\Request;

/**
 * @property-read RecordCategory $resource
 */
class RecordCategoryResource extends BaseJsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return $this->resource->only('id', 'name', 'order');
    }
}
