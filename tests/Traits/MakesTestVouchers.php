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
     * @param Identity $identity
     * @param Fund $fund
     * @return Voucher
     */
    protected function makeTestVoucher(Identity $identity, Fund $fund): Voucher
    {
        return $fund->makeVoucher($identity);
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
