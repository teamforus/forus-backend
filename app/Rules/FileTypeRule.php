<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class FileTypeRule implements Rule
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
        return in_array($value, config('file.allowed_types', []));
    }

    /**
     * Get the validation error message.
     *
     * @return string
     *
     * @psalm-return 'Invalid media type.'
     */
    public function message(): string
    {
        return 'Invalid media type.';
    }
}
