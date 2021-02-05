<?php

namespace App\Http\Requests\Api\Platform\Organizations\Offices;

use App\Http\Requests\BaseFormRequest;

/**
 * Class BaseOfficeRequest
 * @package App\Http\Requests\Api\Platform\Organizations\Offices
 */
abstract class BaseOfficeRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->isAuthenticated();
    }

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
            $dateLocale = $date->startOfWeek()->addDays($week_day)->formatLocalized('%A');

            $attributes["schedule.$week_day.start_time"] = $dateLocale . ' start time';
            $attributes["schedule.$week_day.end_time"] = $dateLocale . ' end time';

            $attributes["schedule.$week_day.break_start_time"] = $dateLocale . ' break start time';
            $attributes["schedule.$week_day.break_end_time"] = $dateLocale . ' break end time';
        }

        return $attributes;
    }

    /**
     * @return array
     */
    protected function updateRules(): array
    {
        return [
            'name' => '',
            'phone' => '',
            'email' => 'nullable|email:strict,dns',
            'address' => 'required',
            'schedule' => 'present|array',
            'schedule.*' => 'required|array',
            'schedule.*.week_day' => 'required|numeric:between:0,6',
            'schedule.*.start_time' => [
                'required_with:schedule.*.end_time',
                'date_format:H:i'
            ],
            'schedule.*.end_time' => [
                'required_with:schedule.*.start_time',
                'date_format:H:i',
                'after:schedule.*.start_time'
            ],
            'schedule.*.break_start_time' => [
                'required_with:schedule.*.break_end_time',
                'date_format:H:i'
            ],
            'schedule.*.break_end_time' => [
                'required_with:schedule.*.break_start_time',
                'date_format:H:i',
                'after:schedule.*.break_start_time'
            ],
        ];
    }
}
