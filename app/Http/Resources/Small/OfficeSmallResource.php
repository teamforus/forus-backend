<?php

namespace App\Http\Resources\Small;

use App\Http\Resources\BaseJsonResource;
use App\Models\Office;
use Illuminate\Http\Request;

/**
 * @property-read Office $resource
 */
class OfficeSmallResource extends BaseJsonResource
{
    public const array LOAD_NESTED = [
        'photo' => MediaSmallResource::class,
    ];

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $office = $this->resource;

        return [
            ...$office->only([
                'id', 'organization_id', 'address', 'phone', 'lat', 'lon',
            ]),
            'photo' => $office->photo ? new MediaSmallResource($office->photo) : null,
        ];
    }
}
