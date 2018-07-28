<?php

namespace App\Rules\Base;

use Illuminate\Contracts\Validation\Rule;

class KvkRule implements Rule
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
        $valid = FALSE;

        try {
            $valid = app()->make('kvk_api')->kvkNumberData($value);
        } catch (\Exception $e) {}

        return $valid;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.kvk');
    }
}
