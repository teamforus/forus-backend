<?php


namespace App\Helpers;

use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;

class Validation
{
    /**
     * @param mixed $value
     * @param string|array $rule
     * @param string $attribute
     * @return Validator
     */
    public static function check(mixed $value, string|array $rule, string $attribute): Validator
    {
        return ValidatorFacade::make(compact('value'), [
            'value' => $rule,
        ], [], [
            'value' => $attribute,
        ]);
    }
}