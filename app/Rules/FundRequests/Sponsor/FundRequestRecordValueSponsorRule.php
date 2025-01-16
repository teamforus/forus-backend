<?php

namespace App\Rules\FundRequests\Sponsor;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\FundRequest;
use App\Rules\FundRequests\BaseFundRequestRule;

class FundRequestRecordValueSponsorRule extends BaseFundRequestRule
{
    /**
     * @param Fund|null $fund
     * @param BaseFormRequest|null $request
     * @param FundCriterion $criterion
     * @param FundRequest $fund_request
     */
    public function __construct(
        protected ?Fund $fund,
        protected ?BaseFormRequest $request,
        protected FundCriterion $criterion,
        protected FundRequest $fund_request,
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
        $values = $this->fund_request->records->pluck('value', 'record_type_key')->toArray();
        $records = $this->criterion->fund_criterion_rules->pluck('record_type_key')->toArray();
        $recordsValues = $this->fund->getTrustedRecordOfTypes($this->request->identity(), $records);

        $values = array_merge($recordsValues, $values);
        $validator = static::validateRecordValue($this->criterion, $value);

        if (!$this->criterion->isExcludedByRules($values) && !$validator->passes()) {
            return $this->reject($validator->errors()->first('value'));
        }

        // Get criteria IDs not excluded by the original rules
        $criteriaIds = $this->fund_request->fund->criteria->filter(
            fn(FundCriterion $criterion) => !$criterion->isExcludedByRules($values)
        )->pluck('id');

        // Get criteria IDs not excluded after applying the new value
        $criteriaIdsAfterUpdate = $this->fund_request->fund->criteria->filter(
            fn(FundCriterion $criterion) => !$criterion->isExcludedByRules([
                ...$values,
                $this->criterion->record_type_key => $value,
            ])
        )->pluck('id');

        // Find differences between the two sets of criteria IDs
        $criteriaDiff = array_merge(
            array_diff($criteriaIds->toArray(), $criteriaIdsAfterUpdate->toArray()),
            array_diff($criteriaIdsAfterUpdate->toArray(), $criteriaIds->toArray())
        );

        // If there are conflicts, set the error message and stop
        if (!empty($criteriaDiff)) {
            $this->messageText = "This value conflicts with an existing rule in the current criteria.";
            return false;
        }

        return true;
    }
}
