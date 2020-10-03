<?php

namespace App\Http\Requests\Api\Platform\Organizations\Offices;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseOfficeRequest extends FormRequest
{
    /**
     * @return array|mixed
     */
    public function attributes(): array
    {
        $date = now();
        $attributes = [];

        $schedule = $this->input('schedule', []);
        $scheduleMap = [];

        if (is_array($schedule)) {
            foreach ($schedule as $index => $day) {
                if (isset($day['week_day']) && is_numeric($day['week_day'])) {
                    $scheduleMap[$index] = $day['week_day'];
                }
            }
        }

        foreach (range(0, 6) as $index) {
            if (!isset($scheduleMap[$index])) {
                continue;
            }

            $week_day = $scheduleMap[$index];

            $attributes["schedule.$week_day.start_time"] =
                $date->startOfWeek()->addDays($week_day)
                    ->formatLocalized('%A') . ' start time';
            $attributes["schedule.$week_day.end_time"] =
                $date->startOfWeek()->addDays($week_day)
                    ->formatLocalized('%A') . ' end time';
            $attributes["schedule.$week_day.break_start_time"] =
                $date->startOfWeek()->addDays($week_day)
                    ->formatLocalized('%A') . ' break start time';
            $attributes["schedule.$week_day.break_end_time"] =
                $date->startOfWeek()->addDays($week_day)
                    ->formatLocalized('%A') . ' break end time';
        }

        return $attributes;
    }
}
