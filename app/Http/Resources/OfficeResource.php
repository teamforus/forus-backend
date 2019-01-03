<?php

namespace App\Http\Resources;

use App\Models\Office;
use Illuminate\Http\Resources\Json\Resource;

class OfficeResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var Office $office */
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
