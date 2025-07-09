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
     * @param array $voucherFields
     * @param int|null $amount
     * @param int|null $limitMultiplier
     * @param bool $dispatchCreated
     * @return Voucher
     */
    protected function makeTestVoucher(
        Fund $fund,
        ?Identity $identity = null,
        array $voucherFields = [],
        ?int $amount = null,
        ?int $limitMultiplier = null,
        bool $dispatchCreated = true,
    ): Voucher {
        return $fund->makeVoucher(
            identity: $identity,
            voucherFields: $voucherFields,
            amount: $amount,
            limitMultiplier: $limitMultiplier,
            dispatchCreated: $dispatchCreated,
        );
    }

    /**
     * @param Fund $fund
     * @param Identity|null $identity
     * @param array $voucherFields
     * @param int|null $productId
     * @param Carbon|null $expireAt
     * @param float|null $price
     * @param bool $dispatchCreated
     * @return Voucher
     */
    protected function makeTestProductVoucher(
        Fund $fund,
        ?Identity $identity = null,
        array $voucherFields = [],
        int $productId = null,
        Carbon $expireAt = null,
        float $price = null,
        bool $dispatchCreated = true,
    ): Voucher {
        return $fund->makeProductVoucher(
            identity: $identity,
            voucherFields: $voucherFields,
            productId: $productId,
            expireAt: $expireAt,
            price: $price,
            dispatchCreated: $dispatchCreated,
        );
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
}
