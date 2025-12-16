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
        $submittedRecordValues = $this->mapRecordValues($this->submittedRecords);

        $hasRequiredGroup = $this->fund
            ->criteria()
            ->whereIn('record_type_key', array_keys($submittedRecordValues))
            ->whereHas('group', function (Builder $builder) use ($value) {
                $builder->where('id', $value);
                $builder->where('required', true);
            })
            ->first()
            ?->group;

        if ($hasRequiredGroup) {
            $groupCriteria = $hasRequiredGroup->fund_criteria->pluck('record_type_key')->toArray();
            $valuesPresents = array_intersect($groupCriteria, array_keys($submittedRecordValues));

            $hasFilled = array_filter(
                $submittedRecordValues,
                fn ($v, $k) => in_array($k, $valuesPresents) && !empty($v),
                ARRAY_FILTER_USE_BOTH
            );

            if (!$hasFilled) {
                return $this->reject(__('validation.fund_request.group_required', ['attribute' => $hasRequiredGroup->title]));
            }
        }

        return true;
    }
}
