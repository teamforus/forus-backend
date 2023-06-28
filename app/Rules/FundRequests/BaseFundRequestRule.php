<?php

namespace App\Rules\FundRequests;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\FundCriterion;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Validation\Rule;

abstract class BaseFundRequestRule implements Rule
{
    protected ?Fund $fund;
    protected string $msg = '';
    protected ?BaseFormRequest $request;

    /**
     * Create a new rule instance.
     *
     * @param Fund|null $fund
     * @param BaseFormRequest|null $request
     */
    public function __construct(?Fund $fund, ?BaseFormRequest $request = null) {
        $this->fund = $fund;
        $this->request = $request;
    }

    /**
     * @param FundCriterion $criterion
     * @param string $attribute
     * @return \Illuminate\Validation\Validator
     */
    protected function validateRecordValue(
        FundCriterion $criterion,
        string $attribute
    ): \Illuminate\Validation\Validator {
        $typesFull = array_pluck(record_types_cached(), 'type', 'key');
        $types = array_map(fn ($value) => $value === 'number' ? 'numeric' : $value, $typesFull);

        $type = $types[$criterion->record_type_key];
        $operator = $criterion->operator;
        $rangeRule = null;

        if ($type == 'numeric') {
            switch ($operator) {
                case '=': {
                    $rangeRule = 'in:' . $criterion->value;
                } break;
                case '<': {
                    $rangeRule = 'max:' . (intval($criterion->value) - 1);
                } break;
                case '<=': {
                    $rangeRule = 'max:' . intval($criterion->value);
                } break;
                case '>': {
                    $rangeRule = 'min:' . (intval($criterion->value) + 1);
                } break;
                case '>=': {
                    $rangeRule = 'min:' . intval($criterion->value);
                } break;
            }
        } else {
            $rangeRule = 'in:' . $criterion->value;
        }

        return Validator::make($this->request->all(), [
            $attribute => ['required', $type, $rangeRule],
        ], [], [
            $attribute => Arr::get(
                Arr::pluck(record_types_cached(), 'name', 'key'),
                $criterion->record_type_key,
                $criterion->record_type_key
            )
        ]);
    }

    /**
     * @param string $attribute
     * @return FundCriterion|string
     */
    protected function findCriterion(string $attribute): FundCriterion|string
    {
        $inputRoot = implode('.', array_slice(explode('.', $attribute), 0, -1));
        $criterionKey = $inputRoot . '.fund_criterion_id';
        $inputRecordTypeKey = $inputRoot . '.record_type_key';
        $criterionId = $this->request->input($criterionKey);
        $typesByKey = @collect(record_types_cached())->keyBy('key');

        /** @var FundCriterion|null $criterion */
        $criterion = $this->fund->criteria->where('id', $criterionId)->first();

        if (!$criterion) {
            return trans('validation.in', [
                'attribute' => $typesByKey[$inputRecordTypeKey] ?? ''
            ]);
        }

        return $criterion;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return $this->msg;
    }
}