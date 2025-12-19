<?php

namespace App\Rules\FundRequests\FundRequestRecords;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Rules\FundRequests\BaseFundRequestRule;
use Illuminate\Database\Eloquent\Builder;

class FundRequestRequiredGroupRule extends BaseFundRequestRule
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
        $submittedValuesByKey = $this->mapRecordValues($this->submittedRecords);

        $requiredGroup = $this->fund->criteria_groups()
            ->where('id', $value)
            ->where('required', true)
            ->whereHas('fund_criteria', function (Builder $builder) use ($submittedValuesByKey) {
                $builder->whereIn('record_type_key', array_keys($submittedValuesByKey));
            })
            ->first();

        if ($requiredGroup) {
            $groupRecordTypeKeys = $requiredGroup->fund_criteria->pluck('record_type_key')->all();
            $groupSubmittedValues = array_intersect_key($submittedValuesByKey, array_flip($groupRecordTypeKeys));

            // TODO: double check later
            // Note: empty() treats 0/'0'/false as empty, adjust if those should count as filled.
            if (!array_filter($groupSubmittedValues, fn ($value) => !empty($value))) {
                return $this->reject(__('validation.fund_request.group_required', ['attribute' => $requiredGroup->title]));
            }
        }

        return true;
    }
}
