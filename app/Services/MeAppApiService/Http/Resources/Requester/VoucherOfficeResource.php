<?php

namespace App\Services\MeAppApiService\Http\Resources\Requester;

use App\Http\Resources\BaseJsonResource;
use App\Models\Office;
use App\Services\MeAppApiService\Http\Resources\Shared\MediaResource;
use App\Services\MeAppApiService\Http\Resources\Shared\OrganizationResource;
use Illuminate\Http\Request;

/**
 * @property-read Office $resource
 */
class VoucherOfficeResource extends BaseJsonResource
{
    public const array LOAD_NESTED = [
        'photo' => MediaResource::class,
        'organization' => OrganizationResource::class,
        'schedules' => OfficeScheduleResource::class,
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $office = $this->resource;

        return [
            ...$office->only([
                'id', 'organization_id', 'address', 'phone', 'lon', 'lat',
                'postcode', 'postcode_number', 'postcode_addition',
            ]),
            'photo' => $office->photo ? new MediaResource($office->photo) : null,
            'organization' => new OrganizationResource($office->organization),
            'schedule' => OfficeScheduleResource::collection($office->schedules),
        ];
    }
}
