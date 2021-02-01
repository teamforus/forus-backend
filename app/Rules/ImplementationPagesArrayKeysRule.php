<?php

namespace App\Rules;

use App\Models\ImplementationPage;
use Illuminate\Contracts\Validation\Rule;

class ImplementationPagesArrayKeysRule implements Rule
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
        return count(array_diff(array_keys($value), ImplementationPage::TYPES)) === 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'Invalid page array or page keys found.';
    }
}
