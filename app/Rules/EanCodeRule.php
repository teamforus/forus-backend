<?php

namespace App\Rules;

class EanCodeRule extends BaseRule
{
    /**
     * @param $attribute
     * @param $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        if (!preg_match('/^\d{8}$|^\d{12}$|^\d{13}$/', $value)) {
            return false;
        }

        return $this->isValidEan($value);
    }

    /**
     * @return string
     */
    public function message(): string
    {
        return trans('validation.ean_code');
    }

    /**
     * @param string $ean
     * @return bool
     */
    private function isValidEan(string $ean): bool
    {
        $length = strlen($ean);
        $sum = 0;

        foreach (str_split($ean) as $i => $digit) {
            $sum += $i % 2 === ($length === 13 || $length === 12 ? 1 : 0) ? $digit * 3 : $digit;
        }

        return $sum % 10 === 0;
    }
}
