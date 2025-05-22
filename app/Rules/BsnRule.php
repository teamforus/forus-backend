<?php

namespace App\Rules;

class BsnRule extends BaseRule
{
    /**
     * Validates if the given value is a valid Dutch BSN number.
     *
     * The function trims the input, checks its length and format, normalizes it,
     * and then applies the validation algorithm based on weights to determine if the
     * BSN is valid. It returns false for invalid cases such as incorrect length,
     * disallowed formats, or failing the checksum calculation.
     *
     * @param string $attribute The name of the attribute being validated.
     * @param mixed $value The value of the attribute being validated.
     * @return bool True if the BSN is valid, otherwise false.
     */
    public function passes($attribute, mixed $value): bool
    {
        $value = (string) $value;

        if (!preg_match('/^[0-9]{8,9}$/', $value)) {
            return false;
        }

        $normalized = str_pad($value, 9, '0', STR_PAD_LEFT);

        if ($normalized === '000000000') {
            return false;
        }

        $weights = [9, 8, 7, 6, 5, 4, 3, 2, -1];
        $sum = 0;

        foreach ($weights as $index => $weight) {
            $sum += (int) $normalized[$index] * $weight;
        }

        return $sum % 11 === 0;
    }

    /**
     * Returns the validation error message for a valid BSN.
     *
     * @return string The error message to display when validation fails.
     */
    public function message(): string
    {
        return trans('validation.bsn');
    }
}
