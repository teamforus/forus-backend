<?php

namespace App\Http\Resources;

use App\Models\ReimbursementCategory;
use Illuminate\Http\Request;

/**
 * @property-read ReimbursementCategory $resource
 */
class ReimbursementCategoryResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return $this->resource->only([
            'id', 'name',
        ]);
    }
}
