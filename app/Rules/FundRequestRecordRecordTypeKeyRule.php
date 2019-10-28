<?php

namespace App\Rules;

use App\Models\Fund;
use App\Models\FundCriterion;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\Request;

class FundRequestRecordRecordTypeKeyRule implements Rule
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

        if ($criterion->record_type_key === $value) {
            return true;
        }

        $this->msg = trans('validation.in', [
            'attribute' => $typesByKey[$value]['name']
        ]);

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
