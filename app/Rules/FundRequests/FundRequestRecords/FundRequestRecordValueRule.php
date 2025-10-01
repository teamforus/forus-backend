<?php

namespace App\Rules\FundRequests\FundRequestRecords;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Rules\FundRequests\BaseFundRequestRule;

class FundRequestRecordValueRule extends BaseFundRequestRule
{
    /**
     * Create a new rule instance.
     *
     * @param Fund|null $fund
     * @param BaseFormRequest|null $request
     * @param array $submittedRecords
     */
    public function __construct(
        protected ?Fund $fund,
        protected ?BaseFormRequest $request,
        protected array $submittedRecords,
    ) {
        parent::__construct($this->fund, $this->request);
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
        $criterion = $this->findCriterion($attribute);
        $submittedRecordValues = $this->mapRecordValues($this->submittedRecords);

        if (!$criterion) {
            return $this->reject(__('validation.in', compact('attribute')));
        }

        $requiredRecordTypes = $criterion->fund_criterion_rules->pluck('record_type_key')->toArray();
        $existingRecordValues = $this->fund->getTrustedRecordOfTypes($this->request->identity(), $requiredRecordTypes);
        $allRecordValues = array_merge($existingRecordValues, $submittedRecordValues);

        if ($criterion->isExcludedByRules($allRecordValues)) {
            return $this->reject(__('validation.fund_request.invalid_record', compact('attribute')));
        }

        $label = $criterion->record_type->translation->name
            ?? $criterion->label
            ?? $criterion->title
            ?? trans('validation.attributes.value');

        $rule = static::recordTypeRuleFor($criterion, $label);

        if (!$rule->passes($attribute, $value)) {
            return $this->reject(trans($rule->message()));
        }

        return true;
    }
}
