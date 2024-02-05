<?php

namespace App\Rules\FundCriteria;

use App\Helpers\Arr;

class FundCriteriaMinRule extends BaseFundCriteriaRule
{
    /**
     * @param $attribute
     * @param $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $criterion = $this->getCriteriaRow($attribute);
        $validation = $this->validateMinMax($attribute, $value, Arr::get($criterion, 'max'));

        return $validation->passes() || $this->reject($validation->errors()->first('min'));
    }
}
