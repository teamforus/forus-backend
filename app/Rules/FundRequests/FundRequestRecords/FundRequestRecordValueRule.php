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
            return $this->reject(trans('validation.in', compact('attribute')));
        }

        $requiredRecordTypes = $criterion->fund_criterion_rules->pluck('record_type_key')->toArray();
        $existingRecordValues = $this->fund->getTrustedRecordOfTypes($this->request->identity(), $requiredRecordTypes);
        $allRecordValues = array_merge($existingRecordValues, $submittedRecordValues);

        if ($criterion->isExcludedByRules($allRecordValues)) {
            return $this->reject(trans('validation.fund_request.invalid_record', compact('attribute')));
        }

        if (($validator = static::validateRecordValue($criterion, $value))->fails()) {
            return $this->reject($validator->errors()->first('value'));
        }

        return true;
    }
}
