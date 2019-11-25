<?php

namespace App\Rules;

use App\Models\Fund;
use Illuminate\Contracts\Validation\Rule;

class VouchersUploadArrayRule implements Rule
{
    protected $fund;
    protected $message;

    /**
     * Create a new rule instance.
     *
     * VouchersUploadArrayRule constructor.
     * @param Fund $fund
     */
    public function __construct(
        Fund $fund = null
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

        if (!is_array($value)) {
            return trans('validation.array');
        }

        if (collect($value)->sum('amount') > $this->fund->budget_left) {
            return 'The sum of the vouchers amount exceeds budget left on the fund.';
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
        return $this->message;
    }
}
