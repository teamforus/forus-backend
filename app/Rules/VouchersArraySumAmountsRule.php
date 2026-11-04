<?php

namespace App\Rules;

use App\Models\Fund;
use App\Models\FundFormula;
use App\Models\Identity;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class VouchersArraySumAmountsRule extends BaseRule
{
    protected Fund $fund;
    protected mixed $vouchers;
    protected ?bool $batchValid = null;

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
        if ($this->batchValid !== null) {
            return $this->batchValid;
        }

        $vouchers = is_array($this->vouchers) ? Arr::pluck($this->vouchers, 'amount') : [];
        $vouchersSum = array_sum(array_filter($vouchers, fn ($amount) => is_numeric($amount)));
        $vouchersSum += $this->multipleVouchersSum();

        if ($vouchersSum > $this->fund->getMaxAmountSumVouchers()) {
            $this->batchValid = false;

            return $this->reject(__('validation.voucher_generator.budget_exceeded'));
        }

        return $this->batchValid = true;
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

        $emailCounts = array_count_values(array_map(fn (string $email) => Str::lower($email), $emails));

        $identities = Identity::listByEmails(array_keys($emailCounts))
            ->load('primary_email')
            ->keyBy(fn (Identity $identity) => Str::lower($identity->primary_email->email));

        return array_sum(array_map(function (string $email, int $count) use ($identities) {
            return $count * $this->fund->amountFundFormulaMultipleVouchers($identities->get($email));
        }, array_keys($emailCounts), array_values($emailCounts)));
    }
}
