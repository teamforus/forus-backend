<?php

namespace App\Rules;

use App\Models\Identity;
use Exception;
use Illuminate\Contracts\Validation\Rule;

class IdentityEmailExistsRule implements Rule
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
        return is_string($value) && Identity::findByEmail($value)?->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.exists');
    }
}
