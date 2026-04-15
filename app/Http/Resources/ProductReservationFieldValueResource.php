<?php

namespace App\Http\Resources;

use App\Models\ProductReservationFieldValue;
use Illuminate\Http\Request;

/**
 * @property-read ProductReservationFieldValue $resource
 */
class ProductReservationFieldValueResource extends BaseJsonResource
{
    public const array LOAD = [];

    public const array LOAD_NESTED = [
        'files' => FileResource::class,
        'reservation_field' => ReservationFieldResource::class,
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
            'files' => FileResource::collection($this->resource->files),
            'reservation_field' => new ReservationFieldResource($this->resource->reservation_field),
        ];
    }
}
