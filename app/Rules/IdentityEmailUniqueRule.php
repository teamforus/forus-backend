<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IdentityEmailUniqueRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct() {}

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     * @throws \Exception
     */
    public function passes($attribute, $value)
    {
        return identity_repo()->isEmailAvailable();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.unique_record', [
            'attribute' => trans('validation.attributes.primary_email')
        ]);
    }
}
