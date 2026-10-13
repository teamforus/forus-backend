<?php

namespace App\Rules;

use App\Models\Fund;
use App\Models\FundFormula;
use App\Models\Identity;
use Illuminate\Support\Arr;

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
        $vouchers = is_array($this->vouchers) ? Arr::pluck($this->vouchers, 'amount') : [];
        $vouchersSum = array_sum(array_filter($vouchers, fn ($amount) => is_numeric($amount)));
        $vouchersSum += $this->multipleVouchersSum();

        if ($vouchersSum > $this->fund->getMaxAmountSumVouchers()) {
            return $this->reject(__('validation.voucher_generator.budget_exceeded'));
        }

        return true;
    }

    /**
     * @return float
     */
    protected function multipleVouchersSum(): float
    {
        $hasFormula = $this->fund->fund_formulas->contains('type', FundFormula::TYPE_MULTIPLY_VOUCHERS);

        if (!$hasFormula || !is_array($this->vouchers)) {
            return 0;
        }

        $emails = array_filter(array_map(function ($voucher) {
            return is_array($voucher) && empty($voucher['product_id']) ? ($voucher['email'] ?? null) : null;
        }, $this->vouchers), fn ($email) => is_string($email) && $email !== '');

        if (empty($emails)) {
            return 0;
        }

        $identities = Identity::query()
            ->whereRelation('primary_email', fn ($query) => $query->whereIn('email', array_unique($emails)))
            ->with('primary_email')
            ->get()
            ->keyBy('primary_email.email');

        return array_sum(array_map(function (string $email) use ($identities) {
            return $this->fund->amountFundFormulaMultipleVouchers($identities->get($email));
        }, $emails));
    }
}
