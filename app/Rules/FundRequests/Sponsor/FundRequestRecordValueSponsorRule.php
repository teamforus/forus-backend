<?php

namespace App\Rules\FundRequests\Sponsor;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\FundCriterion;
use App\Rules\FundRequests\BaseFundRequestRule;

class FundRequestRecordValueSponsorRule extends BaseFundRequestRule
{
    /**
     * @param Fund|null $fund
     * @param BaseFormRequest|null $request
     * @param FundCriterion $criterion
     */
    public function __construct(
        protected ?Fund $fund,
        protected ?BaseFormRequest $request,
        protected FundCriterion $criterion,
    ) {
        parent::__construct($fund, $request);
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
        $validator = static::validateRecordValue($this->criterion, $value);

        if (!$validator->passes()) {
            return $this->reject($validator->errors()->first('value'));
        }

        return true;
    }
}
