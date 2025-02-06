<?php

namespace App\Http\Resources;

use App\Models\OrganizationReservationField;
use Illuminate\Http\Request;

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
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->only([
                'id', 'type', 'organization_id', 'required', 'order',
            ]),
            ...$this->resource->translateColumns($this->resource->only([
                'label', 'description',
            ]))
        ];
    }
}
