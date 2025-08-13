<?php

namespace App\Rules\FundRequests\FundRequestRecords;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Rules\FundRequests\BaseFundRequestRule;

class FundRequestRequiredRecordsRule extends BaseFundRequestRule
{
    /**
     * Create a new rule instance.
     *
     * @param Fund|null $fund
     * @param BaseFormRequest|null $request
     * @param array $submittedRecords
     * @param bool $isValidationRequest
     */
    public function __construct(
        protected ?Fund $fund,
        protected ?BaseFormRequest $request,
        protected array $submittedRecords,
        protected bool $isValidationRequest = false,
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
        // Skip full validation if in single-record validation mode
        if ($this->isValidationRequest) {
            return true;
        }

        // Map submitted records by criterion ID
        $submittedCriteriaById = collect($value)->keyBy('fund_criterion_id')->toArray();
        $submittedRecordValues = $this->mapRecordValues($this->submittedRecords);

        // Track allowed criterion IDs (required and not excluded)
        $expectedCriterionIds = [];

        foreach ($this->fund->criteria as $criterion) {
            $requiredRecordTypes = $criterion
                ->fund_criterion_rules
                ->pluck('record_type_key')
                ->toArray();

            $existingRecordValues = $this->fund->getTrustedRecordOfTypes(
                $this->request->identity(),
                $requiredRecordTypes
            );

            $allRecordValues = array_merge($existingRecordValues, $submittedRecordValues);

            if ($criterion->isExcludedByRules($allRecordValues)) {
                // Excluded: must not be submitted
                if (array_key_exists($criterion->id, $submittedCriteriaById)) {
                    return $this->reject(__('validation.fund_request.invalid_record', [
                        'attribute' => $criterion->record_type_key,
                    ]));
                }
                continue;
            }

            // Not excluded: required and must be submitted
            $expectedCriterionIds[] = $criterion->id;

            if (!array_key_exists($criterion->id, $submittedCriteriaById)) {
                return $this->reject(__('validation.fund_request.required_record', [
                    'attribute' => $criterion->record_type_key,
                ]));
            }
        }

        // Disallow submission of criteria not in the allowed list
        $submittedCriterionIds = array_keys($submittedCriteriaById);
        $unexpectedIds = array_diff($submittedCriterionIds, $expectedCriterionIds);

        if (!empty($unexpectedIds)) {
            return $this->reject(__('validation.fund_request.extra_records'));
        }

        return true;
    }
}
