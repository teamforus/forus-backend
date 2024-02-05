<?php

namespace App\Rules\FundCriteria;

class FundCriteriaKeyRule extends BaseFundCriteriaRule
{
    /**
     * @param $attribute
     * @param $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        if ($this->findRecordType($value)) {
            return true;
        }

        return $this->reject(trans('validation.in', ['attribute' => $attribute]));
    }
}
