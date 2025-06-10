<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\Identity;
use App\Models\Voucher;
use App\Traits\DoesTesting;
use Carbon\Carbon;

trait MakesTestVouchers
{
    use DoesTesting;
    use MakesTestFundProviders;

    /**
     * @param Fund $fund
     * @param Identity|null $identity
     * @param array $fields
     * @param int|null $amount
     * @param int|null $limit_multiplier
     * @return Voucher
     */
    protected function makeTestVoucher(
        Fund $fund,
        ?Identity $identity = null,
        array $fields = [],
        ?int $amount = null,
        ?int $limit_multiplier = null,
    ): Voucher {
        return $fund
            ->makeVoucher($identity, voucherFields: $fields, amount: $amount, limit_multiplier: $limit_multiplier)
            ?->dispatchCreated();
    }

    /**
     * @param Voucher $voucher
     * @param Carbon $date
     * @return Voucher
     */
    protected function expireVoucherAndFund(
        Voucher $voucher,
        Carbon $date,
    ): Voucher {
        $voucher->update([
            'expire_at' => $date,
        ]);

        $voucher->fund->update([
            'end_date' => $date,
            'state' => Fund::STATE_CLOSED,
        ]);

        return $voucher;
    }

    /**
     * @param Fund $fund
     * @param Identity|null $identity
     * @param array $voucherFields
     * @param int|null $product_id
     * @param Carbon|null $expire_at
     * @param float|null $price
     * @return Voucher
     */
    protected function makeTestProductVoucher(
        Fund $fund,
        ?Identity $identity = null,
        array $voucherFields = [],
        int $product_id = null,
        Carbon $expire_at = null,
        float $price = null,
    ): Voucher {
        return $fund
            ->makeProductVoucher($identity, $voucherFields, $product_id, $expire_at, $price)
            ->dispatchCreated(notifyRequesterReserved: false);
    }
}
