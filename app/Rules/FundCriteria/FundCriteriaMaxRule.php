<?php

namespace App\Rules\FundCriteria;

use App\Helpers\Arr;

class FundCriteriaMaxRule extends BaseFundCriteriaRule
{
    /**
     * @param $attribute
     * @param $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $criterion = $this->getCriteriaRow($attribute);
        $validation = $this->validateMinMax($attribute, Arr::get($criterion, 'min'), $value);

        return $validation->passes() || $this->reject($validation->errors()->first('max'));
    }
}
