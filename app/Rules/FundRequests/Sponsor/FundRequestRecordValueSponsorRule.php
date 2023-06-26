<?php

namespace App\Rules\FundRequests\Sponsor;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\FundCriterion;
use App\Rules\FundRequests\BaseFundRequestRule;

class FundRequestRecordValueSponsorRule extends BaseFundRequestRule
{
    protected FundCriterion $criterion;

    public function __construct(?Fund $fund, ?BaseFormRequest $request, FundCriterion $criterion)
    {
        parent::__construct($fund, $request);
        $this->criterion = $criterion;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, mixed $value): bool
    {
        $validator = $this->validateRecordValue($this->criterion, $attribute);

        if (!$validator->passes()) {
            $this->msg = array_first(array_first($validator->errors()->toArray()));
            return false;
        }

        return true;
    }
}
