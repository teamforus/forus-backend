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
        return (preg_replace('/\s+/', '', $value) == $value) && Iban::isValid($value);
    }

    /**
     * Get the validation error message.
     *
     * @return \Illuminate\Contracts\Translation\Translator|array|null|string
     */
    public function message(): array|string|\Illuminate\Contracts\Translation\Translator|null
    {
        return trans('validation.iban');
    }
}
