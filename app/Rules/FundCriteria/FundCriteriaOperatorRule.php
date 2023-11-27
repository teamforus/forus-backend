<?php

namespace App\Rules\FundCriteria;

use Illuminate\Support\Arr;

class FundCriteriaOperatorRule extends BaseFundCriteriaRule
{
    /**
     * @param $attribute
     * @param $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $criterion = $this->getCriteriaRow($attribute);
        $recordType = $this->findRecordType(Arr::get($criterion, 'record_type_key'));
        $operators = $recordType?->getOperators() ?: [];

        if (!$criterion || !$recordType || (!empty($operators) && !in_array($value, $operators))) {
            return $this->reject(trans('validation.in', ['attribute' => $attribute]));
        }

        return true;
    }
}
