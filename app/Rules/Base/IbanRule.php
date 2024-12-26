<?php

namespace App\Rules\Base;

use Faker\Calculator\Iban;
use Illuminate\Contracts\Validation\Rule;

class IbanRule implements Rule
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
        return is_string($value) && (preg_replace('/\s+/', '', $value) == $value) && Iban::isValid($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return trans('validation.iban');
    }
}
