<?php

namespace App\Models\Traits;

use Barryvdh\LaravelIdeHelper\Eloquent;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;

/**
 * @extends Eloquent
 */
trait ValidatesValues
{
    /**
     * @param mixed $value
     * @param $rules
     * @return Validator
     */
    public function validateValue(mixed $value, $rules): Validator
    {
        return static::validateValueStatic($value, $rules);
    }

    /**
     * @param mixed $value
     * @param $rules
     * @return Validator
     */
    public static function validateValueStatic(mixed $value, $rules): Validator
    {
        return ValidatorFacade::make(compact('value'), [
            'value' => $rules,
        ]);
    }
}