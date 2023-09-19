<?php

namespace App\Rules;

use App\Models\Fund;
use Illuminate\Contracts\Validation\Rule;

/**
 * Class VouchersUploadArrayRule
 * @package App\Rules
 */
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
    public function __construct(Fund $fund) {
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
        $vouchers = collect($value);

        if (!$this->fund) {
            return false;
        }

        if (!is_array($value)) {
            return trans('validation.array');
        }

        $invalidAmounts = $vouchers->filter(fn(array $item) => !is_numeric($item['amount'] ?? null));

        if ($invalidAmounts->count() > 0) {
            return trans('validation.voucher_generator.invalid_amounts');
        }

        if ($vouchers->sum('amount') > $this->fund->getMaxAmountSumVouchers()) {
            return trans('validation.voucher_generator.budget_exceeded');
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
        return $this->message;
    }
}
