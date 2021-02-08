<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IdentityRecordsAddressRule implements Rule
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
        return is_string($value) && in_array(count(explode(',', $value)), [2, 3]);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'Invalid :attribute value.';
    }
}
