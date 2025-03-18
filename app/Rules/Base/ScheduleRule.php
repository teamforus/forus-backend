<?php

namespace App\Rules\Base;

use Illuminate\Contracts\Validation\Rule;

class ScheduleRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $reg_ex = "/(2[0-3]|[01][\d]):([0-5][\d])/";

        if (!isset($value['start_time']) || !isset($value['end_time']) ||
            !isset($value['break_start_time']) || !isset($value['break_end_time'])
        ) {
            return false;
        }

        // both are valid format
        return (
            preg_match($reg_ex, $value['start_time']) ||
            $value['start_time'] === 'null'
        ) && (
            preg_match($reg_ex, $value['end_time']) ||
            $value['end_time'] === 'null'
        ) && (
            preg_match($reg_ex, $value['break_start_time']) ||
            $value['break_start_time'] === 'null'
        ) && (
            preg_match($reg_ex, $value['break_end_time']) ||
            $value['break_end_time'] === 'null'
        );
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.schedule');
    }
}
