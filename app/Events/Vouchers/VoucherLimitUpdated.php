<?php

namespace App\Events\Vouchers;

use App\Models\Voucher;

class VoucherLimitUpdated extends BaseVoucherEvent
{
    protected int $oldLimitMultiplier;

    /**
     * @return int
     */
    public function getOldLimitMultiplier(): int
    {
        return $this->oldLimitMultiplier;
    }
}
