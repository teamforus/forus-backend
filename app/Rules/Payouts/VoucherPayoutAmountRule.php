<?php

namespace App\Rules\Payouts;

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

        $fixedAmount = $this->voucher->fund?->fund_config?->allow_voucher_payout_amount;
        $balance = (float) $this->voucher->amount_available;
        $balanceCents = self::toCents($balance);
        $amountCents = self::toCents((float) $value);

        if ($fixedAmount !== null) {
            $fixedAmountCents = self::toCents((float) $fixedAmount);

            if ($amountCents !== $fixedAmountCents) {
                return $this->reject(trans('validation.payout.amount_exact', [
                    'amount' => currency_format_locale((float) $fixedAmount),
                ]));
            }

            if (self::exceedsBalance((float) $value, $balance)) {
                return $this->reject(trans('validation.voucher.not_enough_funds'));
            }

            return true;
        }

        $minAmountCents = self::toCents($minAmount);

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
     * @return int
     */
    public static function toCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /**
     * @param float $amount
     * @param float $balance
     * @return bool
     */
    public static function exceedsBalance(float $amount, float $balance): bool
    {
        return self::toCents($amount) > self::toCents($balance);
    }

    /**
     * @param Voucher $voucher
     * @return string
     */
    public static function balanceExceededMessage(Voucher $voucher): string
    {
        $fixedAmount = $voucher->fund?->fund_config?->allow_voucher_payout_amount;
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
