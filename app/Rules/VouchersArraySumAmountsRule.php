<?php

namespace App\Rules;

use App\Models\Fund;

class VouchersArraySumAmountsRule extends BaseRule
{
    protected Fund $fund;
    protected mixed $vouchers;

    /**
     * @param Fund $fund
     * @param array $vouchers
     */
    public function __construct(Fund $fund, mixed $vouchers)
    {
        $this->fund = $fund;
        $this->vouchers = $vouchers;
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
        $vouchers = is_array($this->vouchers) ? array_pluck($this->vouchers, 'amount') : [];
        $vouchersSum = array_sum(array_filter($vouchers, fn ($amount) => is_numeric($amount)));

        if ($vouchersSum > $this->fund->getMaxAmountSumVouchers()) {
            return $this->reject(trans('validation.voucher_generator.budget_exceeded'));
        }

        return true;
    }
}
