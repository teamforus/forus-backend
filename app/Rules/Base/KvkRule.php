<?php

namespace App\Rules\Base;

use Illuminate\Contracts\Validation\Rule;

class KvkRule implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        try {
            return (bool) resolve('kvk_api')->kvkNumberData($value);
        } catch (\Exception $e) {
            logger()->error($e->getMessage());
            return false;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return trans('validation.kvk');
    }
}
