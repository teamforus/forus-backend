<?php

namespace App\Rules;

class MaxStringRule extends BaseRule
{
    private string $message;

    /**
     * @param int $max
     */
    public function __construct(protected int $max = 500)
    {
    }

    /**
     * @param $attribute
     * @param $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        if (strlen($value) > $this->max) {
            $this->message = trans('validation.rules.max.string', [
                'attribute' => $attribute,
                'max' => $this->max,
                'actual' => strlen($value),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return $this->message;
    }
}
