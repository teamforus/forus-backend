<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

class OfficeScheduleResource extends Resource
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
            'id', 'office_id', 'week_day', 'start_time', 'end_time'
        ])->toArray();
    }
}
