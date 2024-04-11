<?php

namespace App\Events\Vouchers;

use App\Models\Voucher;

class VoucherAssigned extends BaseVoucherEvent
{
    protected bool $notifyRequesterAssigned;

    /**
     * Create a new event instance.
     *
     * @param Voucher $voucher
     * @param bool $notifyRequesterAssigned
     */
    public function __construct(
        Voucher $voucher,
        bool $notifyRequesterAssigned = true,
    ) {
        parent::__construct($voucher);

        $this->notifyRequesterAssigned = $notifyRequesterAssigned;
    }

    /**
     * @return bool
     */
    public function shouldNotifyRequesterAssigned(): bool
    {
        return $this->notifyRequesterAssigned;
    }
}
