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
        $requestCriteria = collect($value)->keyBy('fund_criterion_id')->toArray();
        $values = $this->mapRecordValues($this->records);

        foreach ($this->fund->criteria as $criterion) {
            $records = $criterion->fund_criterion_rules->pluck('record_type_key')->toArray();
            $recordsValues = $this->fund->getTrustedRecordOfTypes($this->request->identity(), $records);
            $allValues = array_merge($recordsValues, $values);

            if (!$criterion->isExcludedByRules($allValues) && !array_key_exists($criterion->id, $requestCriteria)) {
                return $this->reject(trans('validation.required', [
                    'attribute' => $criterion->record_type_key
                ]));
            }
        }

        return true;
    }
}
