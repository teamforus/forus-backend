<?php

namespace App\Rules;

use App\Models\Fund;
use App\Models\FundCriterion;
use Illuminate\Contracts\Validation\Rule;

class FundRequestFilesRule implements Rule
{
    protected $fund;
    protected $messageValue;

    /**
     * Create a new rule instance.
     *
     * @param Fund $fund
     */
    public function __construct(Fund $fund)
    {
        $this->fund = $fund;
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
        /** @var FundCriterion|null $criteria */
        $criteria = $this->fund->criteria()->find($value['fund_criterion_id'] ?? false);
        $files = $value['files'] ?? [];

        if (!$criteria) {
            $this->messageValue = trans('validation.in', [
                'attribute' => trans('validation.attributes.file'),
            ]);
            return false;
        }

        if ($criteria->show_attachment && (!is_array($files) || count($files) < 1)) {
            $this->messageValue = trans('validation.required', [
                'attribute' => trans('validation.attributes.file'),
            ]);
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return $this->messageValue;
    }
}
