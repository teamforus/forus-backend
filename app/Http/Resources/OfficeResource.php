<?php

namespace App\Http\Resources;

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
        return collect($this->resource)->only([
            'id', 'organization_id', 'address', 'phone', 'email',
            'lon', 'lat'
        ])->merge([
            'photo' => new MediaResource($this->resource->photo),
            'schedule' => OfficeScheduleResource::collection(
                $this->resource->schedules
            )
        ])->toArray();
    }
}
