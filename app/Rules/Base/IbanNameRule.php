<?php

namespace App\Rules\Base;

use App\Helpers\Validation;
use Illuminate\Contracts\Validation\Rule;

class IbanNameRule implements Rule
{
    protected string $errorMessage;

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $rules = Validation::check($value, ['string', 'min:3', 'max:280']);

        if ($rules->passes()) {
            return true;
        }

        $this->errorMessage = $rules->errors()?->first();

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return $this->errorMessage;
    }
}
