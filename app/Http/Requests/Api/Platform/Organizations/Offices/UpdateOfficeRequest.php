<?php

namespace App\Http\Requests\Api\Platform\Organizations\Offices;

class UpdateOfficeRequest extends BaseOfficeRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => '',
            'phone' => '',
            'address' => 'required',
            'email' => 'nullable|email:strict,dns',
            'schedule' => 'required|array',
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
