<?php

namespace App\Rules;

use App\Models\Fund;
use App\Models\FundCriterion;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FundRequestRecordValueRule implements Rule
{
    protected $msg;
    protected $fund;
    protected $request;

    /**
     * Create a new rule instance.
     *
     * @param Request $request
     * @param Fund|null $fund
     */
    public function __construct(
        Request $request,
        ?Fund $fund
    ) {
        $this->request = $request;
        $this->fund = $fund;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool|void
     */
    public function passes($attribute, $value)
    {
        $inputRoot = implode('.', array_slice(explode('.', $attribute), 0, -1));
        $criterionKey =  $inputRoot . '.fund_criterion_id';
        $inputRecordTypeKey =  $inputRoot . '.record_type_key';
        $criterionId = $this->request->input($criterionKey, null);

        $typesFull = record_types_cached();
        $typesByKey = @collect($typesFull)->keyBy('key');

        /** @var FundCriterion|null $criterion */
        $criterion = $this->fund->criteria->where('id', $criterionId)->first();

        if (!$criterion) {
            $this->msg = trans('validation.in', [
                'attribute' => $typesByKey[$inputRecordTypeKey] ?? ''
            ]);
            return false;
        }

        $typesFull = record_types_cached();
        $types = array_map(function($value) {
            return $value === 'number' ? 'numeric' : $value;
        }, array_pluck($typesFull, 'type', 'key'));
        $type = $types[$criterion->record_type_key];
        $operator = $criterion->operator;
        $rangeRule = null;

        if ($type == 'numeric') {
            switch ($operator) {
                case '=': {
                    $rangeRule = 'in:' . $criterion->value;
                }; break;
                case '<': {
                    $rangeRule = 'max:' . (intval($criterion->value) - 1);
                }; break;
                case '<=': {
                    $rangeRule = 'max:' . intval($criterion->value);
                }; break;
                case '>': {
                    $rangeRule = 'min:' . (intval($criterion->value) + 1);
                }; break;
                case '>=': {
                    $rangeRule = 'min:' . intval($criterion->value);
                }; break;
            }
        } else {
            $rangeRule = 'in:' . $criterion->value;
        }

        $validator = Validator::make($this->request->all(), [
            $attribute => ['required', $type, $rangeRule],
        ], [], [
            $attribute => @collect($typesFull)->keyBy('key')[$criterion->record_type_key]['name'],
        ]);

        if ($validator->passes()) {
            return true;
        }

        $this->msg = array_first(array_first($validator->errors()->toArray()));

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->msg;
    }
}
