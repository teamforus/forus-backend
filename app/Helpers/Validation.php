<?php

namespace App\Helpers;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;

class Validation
{
    /**
     * @param mixed $value
     * @param string|array|Rule $rule
     * @param string|null $attribute
     * @return Validator
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
