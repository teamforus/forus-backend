<?php

namespace App\Http\Resources;

use App\Models\OfficeSchedule;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class OfficeScheduleResource
 * @property OfficeSchedule $resource
 * @package App\Http\Resources
 */
class OfficeScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request|any  $request
     * @return array
     */
    public function toArray($request): array
    {
        $schedule = $this->resource;

        return array_merge($schedule->only([
            'id', 'office_id', 'week_day', 'start_time', 'end_time',
            'break_start_time', 'break_end_time',
        ]), [
            'start_time' => substr($schedule->start_time, 0, 5),
            'end_time' => substr($schedule->end_time, 0, 5),
            'break_start_time' => substr($schedule->break_start_time, 0, 5),
            'break_end_time' => substr($schedule->break_end_time, 0, 5),
        ]);
    }
}
