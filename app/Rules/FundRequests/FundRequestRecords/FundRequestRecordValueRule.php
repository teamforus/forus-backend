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
     * @param array $records
     */
    public function __construct(
        protected ?Fund $fund,
        protected ?BaseFormRequest $request,
        protected array $records,
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
        $values = $this->mapRecordValues($this->records);

        if (!$criterion) {
            return $this->reject(trans('validation.in', compact('attribute')));
        }

        $records = $criterion->fund_criterion_rules->pluck('record_type_key')->toArray();
        $recordsValues = $this->fund->getTrustedRecordOfTypes($this->request->identity(), $records);

        $allValues = array_merge($recordsValues, $values);
        $isExcluded = $criterion->isExcludedByRules($allValues);

        if ($isExcluded) {
            return $this->reject(trans('validation.in', compact('attribute')));
        }

        if (($validator = static::validateRecordValue($criterion, $value))->fails()) {
            return $this->reject($validator->errors()->first('value'));
        }

        return true;
    }
}
