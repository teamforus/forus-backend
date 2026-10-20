<?php

namespace App\Services\MeAppApiService\Http\Resources\Requester;

use App\Http\Resources\BaseJsonResource;
use App\Models\OfficeSchedule;
use Illuminate\Http\Request;

/**
 * @property-read OfficeSchedule $resource
 */
class OfficeScheduleResource extends BaseJsonResource
{
    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $schedule = $this->resource;

        return [
            ...$schedule->only([
                'id', 'office_id', 'week_day',
            ]),
            'start_time' => substr($schedule->start_time ?? '', 0, 5),
            'end_time' => substr($schedule->end_time ?? '', 0, 5),
        ];
    }
}
