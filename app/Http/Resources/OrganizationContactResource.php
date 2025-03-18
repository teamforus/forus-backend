<?php

namespace App\Http\Resources;

use App\Models\OrganizationContact;
use Throwable;

/**
 * @property OrganizationContact $resource
 */
class OrganizationContactResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @throws Throwable
     * @return array
     */
    public function toArray($request): array
    {
        return $this->resource->only([
            'id', 'type', 'key', 'value', 'organization_id',
        ]);
    }
}
