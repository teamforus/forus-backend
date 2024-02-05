<?php

namespace App\Rules\FundRequests\FundRequestRecords;

use App\Rules\FundRequests\BaseFundRequestRule;

class FundRequestRecordValueRule extends BaseFundRequestRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, mixed $value): bool
    {
        $criterion = $this->findCriterion($attribute);

        if (!$criterion) {
            return $this->reject(trans('validation.in', compact('attribute')));
        }

        if (($validator = static::validateRecordValue($criterion, $value))->fails()) {
            return $this->reject($validator->errors()->first('value'));
        }

        return true;
    }
}
