<?php

namespace App\Rules\Payouts;

use App\Models\Voucher;
use App\Rules\BaseRule;
use App\Scopes\Builders\VoucherTransactionQuery;

class VoucherPayoutCountRule extends BaseRule
{
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
        if (!$this->voucher) {
            return true;
        }

        $limit = $this->voucher->fund?->fund_config?->allow_voucher_payout_count;

        if ($limit === null) {
            return true;
        }

        $limit = (int) $limit;
        $count = VoucherTransactionQuery::countRequesterPayouts($this->voucher);

        if ($count >= $limit) {
            return $this->reject(self::countReachedMessage($limit));
        }

        return true;
    }

    /**
     * @param int $limit
     * @return string
     */
    public static function countReachedMessage(int $limit): string
    {
        return trans('validation.payout.count_reached', [
            'count' => $limit,
        ]);
    }
}
