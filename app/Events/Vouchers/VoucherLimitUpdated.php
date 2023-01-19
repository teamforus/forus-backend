<?php

namespace App\Events\Vouchers;

use App\Models\Voucher;

class VoucherLimitUpdated extends BaseVoucherEvent
{
    protected int $oldLimitMultiplier;

    /**
     * @param Voucher $voucher
     * @param int $oldLimitMultiplier
     */
    public function __construct(Voucher $voucher, int $oldLimitMultiplier)
    {
        parent::__construct($voucher);

        $this->oldLimitMultiplier = $oldLimitMultiplier;
    }

    /**
     * @return int
     */
    public function getOldLimitMultiplier(): int
    {
        return $this->oldLimitMultiplier;
    }
}
