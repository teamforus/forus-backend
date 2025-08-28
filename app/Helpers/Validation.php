<?php

namespace App\Helpers;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;

class Validation
{
    /**
     * Creates a validator instance to check a single value against one or more rules.
     *
     * @param mixed $value The value to be validated.
     * @param string|array|Rule $rule The validation rule(s) to apply.
     * @param string|null $attribute The attribute name for error messages.
     * @return Validator The validator instance.
     */
    public static function check(mixed $value, string|array|Rule $rule, string $attribute = null): Validator
    {
        return ValidatorFacade::make(compact('value'), [
            'value' => $rule,
        ], [], [
            'value' => $attribute,
        ]);
    }
}
