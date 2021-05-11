<?php

namespace App\Http\Resources;

use App\Models\Office;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class OfficeResource
 * @property Office $resource
 * @package App\Http\Resources
 */
class OfficeResource extends Resource
{
    public static $load = [
        'photo.presets', 'organization.business_type.translations',
        'organization.logo', 'schedules',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request|any  $request
     * @return array|null
     */
    public function toArray($request): ?array
    {
        if ($this->resource === null) {
            return null;
        }

        $office = $this->resource;
        $organization = $office->organization;

        return array_merge($office->only([
            'id', 'organization_id', 'address', 'phone', 'lon', 'lat',
            'postcode', 'postcode_number', 'postcode_addition',
        ]), [
            'photo' => new MediaResource($office->photo),
            'organization' => new OrganizationBasicResource($organization),
            'schedule' => OfficeScheduleResource::collection($office->schedules)
        ]);
    }
}
