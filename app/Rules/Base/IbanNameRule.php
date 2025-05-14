<?php

namespace App\Rules\Base;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IbanNameRule implements ValidationRule
{
    /**
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $attribute = trans('validation.attributes.iban_name');
        $min = 3;
        $max = 200;

        if (!is_string($value)) {
            $fail(trans('validation.string', compact('attribute')));
        }

        if (!preg_match('/^[a-zA-Z .]+$/', $value)) {
            $fail(trans('validation.iban_name', compact('attribute')));
        }

        $length = mb_strlen($value);

        if ($length < $min) {
            $fail(ucfirst(trans('validation.min.string', compact('attribute', 'min'))));
        }

        if ($length > $max) {
            $fail(ucfirst(trans('validation.max.string', compact('attribute', 'max'))));
        }
    }
}
