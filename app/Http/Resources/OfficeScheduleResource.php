<?php

namespace App\Http\Resources;

use App\Models\OfficeSchedule;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class OfficeScheduleResource
 * @property OfficeSchedule $resource
 * @package App\Http\Resources
 */
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
        ])->merge([
            'start_time' => $this->resource ? collect(
                explode(':', $this->resource->start_time)
            )->splice(0, 2)->implode(':'): null,
            'end_time' => $this->resource ? collect(
                explode(':', $this->resource->end_time)
            )->splice(0, 2)->implode(':'): null,
            'break_start_time' => $this->resource ? collect(
                explode(':', $this->resource->break_start_time)
            )->splice(0, 2)->implode(':'): null,
            'break_end_time' => $this->resource ? collect(
                explode(':', $this->resource->break_end_time)
            )->splice(0, 2)->implode(':'): null
        ])->toArray();
    }
}
