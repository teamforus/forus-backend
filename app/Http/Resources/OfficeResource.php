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
        'photo.sizes', 'organization.business_type.translations',
        'organization.logo', 'schedules',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $office = $this->resource;
        $organization = $office->organization;

        return collect($office)->only([
            'id', 'organization_id', 'address', 'phone', 'lon', 'lat'
        ])->merge([
            'photo' => new MediaResource($office->photo),
            'organization' => new OrganizationBasicResource($organization),
            'schedule' => OfficeScheduleResource::collection(
                $office->schedules
            )
        ])->toArray();
    }
}
