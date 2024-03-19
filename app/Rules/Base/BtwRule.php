<?php

namespace App\Rules\Base;

use Illuminate\Contracts\Validation\Rule;

class BtwRule implements Rule
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
     * @param string  $attribute
     * @param mixed  $value
     *
     * @return true
     */
    public function passes($attribute, $value)
    {
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return \Illuminate\Contracts\Translation\Translator|array|null|string
     */
    public function message(): array|string|\Illuminate\Contracts\Translation\Translator|null
    {
        return trans('validation.btw');
    }
}
