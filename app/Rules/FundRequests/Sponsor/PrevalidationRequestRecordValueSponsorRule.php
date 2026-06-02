<?php

namespace App\Rules\FundRequests\Sponsor;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\PrevalidationRequest;
use App\Models\PrevalidationRequestRecord;
use App\Rules\FundRequests\BaseFundRequestRule;

class PrevalidationRequestRecordValueSponsorRule extends BaseFundRequestRule
{
    /**
     * @param Fund|null $fund
     * @param BaseFormRequest|null $request
     * @param PrevalidationRequestRecord $record
     * @param PrevalidationRequest $prevalidation_request
     */
    public function __construct(
        protected ?Fund $fund,
        protected ?BaseFormRequest $request,
        protected PrevalidationRequestRecord $record,
        protected PrevalidationRequest $prevalidation_request,
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
        $values = $this->prevalidation_request->records->pluck('value', 'record_type_key')->toArray();

        /** @var FundCriterion $criterion */
        $criterion = $this->fund->criteria->where(function (FundCriterion $criterion) use ($values) {
            return
                $criterion->record_type_key === $this->record->record_type_key &&
                !$criterion->isExcludedByRules($values);
        })->first();

        if ($criterion) {
            $validation = BaseFundRequestRule::validateRecordValue($criterion, $value);

            if (!$validation->passes()) {
                return $this->reject($validation->errors()->first('value'));
            }

            return true;
        }

        $validator = static::validateSponsorRecordValueByType($this->record->record_type, $value);

        if (!$validator->passes()) {
            return $this->reject($validator->errors()->first('value'));
        }

        return true;
    }
}
