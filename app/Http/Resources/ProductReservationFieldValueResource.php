<?php

namespace App\Http\Resources;

use App\Models\ProductReservationFieldValue;
use Illuminate\Http\Request;

/**
 * @property-read ProductReservationFieldValue $resource
 */
class ProductReservationFieldValueResource extends BaseJsonResource
{
    public const array LOAD = [
        'files',
        'reservation_field',
    ];

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
                'id', 'value',
            ]),
            'label' => $this->resource->reservation_field?->label,
            'file' => new FileResource($this->resource->files[0] ?? null),
        ];
    }
}
