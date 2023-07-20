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
     * @return array
     * @throws Throwable
     */
    public function toArray($request): array
    {
        return $this->resource->only([
            'id', 'type', 'contact_key', 'value', 'organization_id',
        ]);
    }
}
