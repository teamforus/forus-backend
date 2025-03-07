<?php

namespace App\Rules;

use App\Models\Fund;
use App\Models\FundCriterion;
use App\Rules\FundRequests\BaseFundRequestRule;
use Illuminate\Support\Collection;

class PrevalidationDataItemRule extends BaseRule
{
    /**
     * Create a new rule instance.
     *
     * @param Collection $recordTypes
     * @param Fund|null $fund
     * @param array|null $data
     */
    public function __construct(
        protected Collection $recordTypes,
        protected ?Fund $fund = null,
        protected ?array $data = []
    ) {
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $recordKey = last(explode('.', $attribute));
        $index = explode('.', $attribute)[1];
        $records = $this->data[$index] ?? [];

        if (!($this->recordTypes[$recordKey] ?? false)) {
            return $this->rejectTrans('validation.prevalidation_invalid_record_key');
        }

        if ($recordKey === 'primary_email') {
            return $this->rejectTrans('validation.prevalidation_invalid_type_primary_email');
        }

        if ($this->fund && !$this->validateRecord($recordKey, $value, $records)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param array $values
     * @return bool
     */
    private function validateRecord(string $key, mixed $value, array $values): bool
    {
        /** @var FundCriterion $criterion */
        $criterion = $this->fund->criteria->where(function (FundCriterion $criterion) use ($key, $values) {
            return
                $criterion->record_type_key === $key &&
                !$criterion->isExcludedByRules($values);
        })->first();

        $validation = $criterion
            ? BaseFundRequestRule::validateRecordValue($criterion, $value)
            : null;

        if ($validation && !$validation->passes()) {
            $this->messageText = $validation->errors()->first('value');

            return false;
        }

        return true;
    }
}
