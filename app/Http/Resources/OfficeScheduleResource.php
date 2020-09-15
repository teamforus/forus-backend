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
     * @param  \Illuminate\Http\Request|any  $request
     * @return array
     */
    public function toArray($request): array
    {
        return array_merge($this->resource->only([
            'id', 'office_id', 'week_day',
            'start_time', 'end_time',
            'break_start_time', 'break_end_time'
        ]), [
            'start_time' => $this->resource ? substr($this->resource->start_time, 0, 5): null,
            'end_time' => $this->resource ? substr($this->resource->end_time, 0, 5): null,
            'break_start_time' => $this->resource ? substr($this->resource->break_start_time, 0, 5): null,
            'break_end_time' => $this->resource ? substr($this->resource->break_end_time, 0, 5): null,
        ]);
    }
}
