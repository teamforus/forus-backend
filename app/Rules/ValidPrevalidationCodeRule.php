<?php

namespace App\Rules;

use App\Models\Fund;
use App\Models\Prevalidation;
use Illuminate\Contracts\Validation\Rule;

class ValidPrevalidationCodeRule implements Rule
{
    private $fund = null;
    private $msg = null;

    /**
     * Create a new rule instance.
     *
     * ValidPrevalidationCodeRule constructor.
     * @param Fund|null $fund
     */
    public function __construct(
        ?Fund $fund = null
    ) {
        $this->fund = $fund;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (!$this->fund) {
            return false;
        }

        $prevalidation = Prevalidation::where([
            'fund_id' => $this->fund->id,
            'uid' => $value
        ])->first();

        if (!$prevalidation) {
            $this->msg = trans('validation.prevalidation_code.not_found');
            return false;
        }

        if ($prevalidation->state == Prevalidation::STATE_USED) {
            $this->msg = trans('validation.prevalidation_code.used');
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->msg ?: 'Invalid prevalidation code.';
    }
}
