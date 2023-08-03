<?php

namespace App\Http\Resources;

use App\Models\OrganizationReservationField;

/**
 * @property-read OrganizationReservationField $resource
 */
class OrganizationReservationFieldResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return $this->resource->only([
            'id', 'type', 'organization_id', 'label', 'description', 'required', 'order',
        ]);
    }
}
