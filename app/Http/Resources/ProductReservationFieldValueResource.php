<?php

namespace App\Http\Resources;

use App\Models\ProductReservationFieldValue;

/**
 * @property-read ProductReservationFieldValue $resource
 */
class ProductReservationFieldValueResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return array_merge($this->resource->only('id', 'value'), [
            'label' => $this->resource->organization_reservation_field?->label
        ]);
    }
}
