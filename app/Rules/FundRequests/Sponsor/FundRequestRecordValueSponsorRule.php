<?php

namespace App\Rules\FundRequests\Sponsor;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Rules\FundRequests\BaseFundRequestRule;

class FundRequestRecordValueSponsorRule extends BaseFundRequestRule
{
    /**
     * @param Fund|null $fund
     * @param BaseFormRequest|null $request
     * @param FundRequestRecord $record
     * @param FundRequest $fund_request
     */
    public function __construct(
        protected ?Fund $fund,
        protected ?BaseFormRequest $request,
        protected FundRequestRecord $record,
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

        if ($this->record->fund_criterion) {
            $records = $this->record->fund_criterion->fund_criterion_rules->pluck('record_type_key')->toArray();
            $recordsValues = $this->fund->getTrustedRecordOfTypes($this->request->identity(), $records);

            $values = array_merge($recordsValues, $values);

            $validator = static::validateRecordValue($this->record->fund_criterion, $value);

            if (!$this->record->fund_criterion->isExcludedByRules($values) && !$validator->passes()) {
                return $this->reject($validator->errors()->first('value'));
            }

            return true;
        } elseif ($this->record->source === $this->record::SOURCE_BRP) {
            $validator = static::validateSponsorRecordValueByType($this->record->record_type, $value);

            if (!$validator->passes()) {
                return $this->reject($validator->errors()->first('value'));
            }

            return true;
        }

        return $this->reject(trans('validation.fund_request.record_edit_forbidden'));
    }
}
