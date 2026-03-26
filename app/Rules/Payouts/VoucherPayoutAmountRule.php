<?php

namespace App\Rules\Payouts;

use App\Helpers\Number;
use App\Models\Voucher;
use App\Rules\BaseRule;

class VoucherPayoutAmountRule extends BaseRule
{
    public const float MIN_AMOUNT = 0.1;

    /**
     * @param Voucher|null $voucher
     */
    public function __construct(protected ?Voucher $voucher)
    {
    }

    /**
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $minAmount = self::MIN_AMOUNT;

        if (!$this->voucher) {
            return true;
        }

        $partialAmounts = $this->voucher->getPayoutPartialAmounts();

        if (is_array($partialAmounts)) {
            $allowedCents = array_map(fn (string $amount) => Number::toCents((float) $amount), $partialAmounts);

            if (!in_array(Number::toCents((float) $value), $allowedCents, true)) {
                return $this->reject(trans('validation.payout.amount_partial'));
            }

            return true;
        }

        $fixedAmount = $this->voucher->fund?->voucherPayoutAmountForIdentity($this->voucher->identity);
        $balance = (float) $this->voucher->amount_available;
        $balanceCents = Number::toCents($balance);
        $amountCents = Number::toCents((float) $value);

        if ($fixedAmount !== null) {
            $fixedAmountCents = Number::toCents($fixedAmount);

            if ($amountCents !== $fixedAmountCents) {
                return $this->reject(trans('validation.payout.amount_exact', [
                    'amount' => currency_format_locale($fixedAmount),
                ]));
            }

            if (self::exceedsBalance((float) $value, $balance)) {
                return $this->reject(trans('validation.voucher.not_enough_funds'));
            }

            return true;
        }

        $minAmountCents = Number::toCents($minAmount);

        if ($amountCents < $minAmountCents || $amountCents > $balanceCents) {
            return $this->reject(trans('validation.payout.amount_between', [
                'min' => currency_format_locale($minAmount),
                'max' => currency_format_locale($balance),
            ]));
        }

        return true;
    }

    /**
     * @param float $amount
     * @param float $balance
     * @return bool
     */
    public static function exceedsBalance(float $amount, float $balance): bool
    {
        return Number::toCents($amount) > Number::toCents($balance);
    }

    /**
     * @param Voucher $voucher
     * @return string
     */
    public static function balanceExceededMessage(Voucher $voucher): string
    {
        $fixedAmount = $voucher->fund?->voucherPayoutAmountForIdentity($voucher->identity);
        $balance = (float) $voucher->amount_available;

        if ($fixedAmount !== null) {
            return trans('validation.voucher.not_enough_funds');
        }

        return trans('validation.payout.amount_between', [
            'min' => currency_format_locale(self::MIN_AMOUNT),
            'max' => currency_format_locale($balance),
        ]);
    }
}
