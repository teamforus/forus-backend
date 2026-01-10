<?php

namespace App\Http\Resources;

use App\Models\ReservationField;
use Illuminate\Http\Request;

/**
 * @property-read ReservationField $resource
 */
class ReservationFieldResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->only([
                'id', 'type', 'organization_id', 'required', 'order', 'product_id', 'fillable_by',
            ]),
            ...$this->resource->translateColumns($this->resource->only([
                'label', 'description',
            ])),
        ];
    }
}
