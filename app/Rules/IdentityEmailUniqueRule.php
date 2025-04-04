<?php

namespace App\Rules;

use App\Models\Identity;
use Exception;
use Illuminate\Contracts\Validation\Rule;

class IdentityEmailUniqueRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @throws Exception
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        return Identity::isEmailAvailable($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return trans('validation.email_already_used');
    }
}
