<?php

namespace App\Helpers;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;

class Validation
{
    /**
     * Function: for fast validating value, use checkWithLabels() to validate value and have corresponding keys and labels
     *
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

    /**
    * Function: used for validating value and creating corresponding keys and labels
    *
    * @param string $field
    * @param mixed $value
    * @param string|array|Rule $rule
    *  @param array $messages
    * @param array $attributes
    * @return Validator
    */
    public static function checkWithLabels(
        string $field,
        mixed $value,
        string|array|Rule $rule,
        array $messages = [],
        array $attributes = []
    ): Validator {

        $data = [];
        Arr::set($data, $field, $value);
//        $rules = is_array($rule) ? $rule : [$rule];

        return ValidatorFacade::make(
            $data,
            [$field => $rule],
            $messages,
            $attributes,
        );
    }
}
