<?php

namespace App\Rules;

use App\Helpers\Validation;

class BsnRule extends BaseRule
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
        $validation = Validation::check($value, 'required|digits_between:8,9');

        if (!$validation->passes()) {
            return $this->reject($validation->errors()->first('value'));
        }

        return true;
    }
}
