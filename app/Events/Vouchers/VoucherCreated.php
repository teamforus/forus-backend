<?php

namespace App\Events\Vouchers;

use App\Models\Voucher;

/**
 * Class VoucherCreated
 * @package App\Events\Vouchers
 */
class VoucherCreated extends BaseVoucherEvent
{
    protected bool $notifyRequesterAdded;
    protected bool $notifyRequesterReserved;

    /**
     * Create a new event instance.
     *
     * @param Voucher $voucher
     * @param bool $notifyRequesterReserved
     * @param bool $notifyRequesterAdded
     */
    public function __construct(
        Voucher $voucher,
        bool $notifyRequesterReserved = true,
        bool $notifyRequesterAdded = true
    ) {
        parent::__construct($voucher);

        $this->notifyRequesterAdded = $notifyRequesterAdded;
        $this->notifyRequesterReserved = $notifyRequesterReserved;
    }

    /**
     * @return bool
     */
    public function shouldNotifyRequesterReserved(): bool
    {
        return $this->notifyRequesterReserved;
    }

    /**
     * @return bool
     */
    public function shouldNotifyRequesterAdded(): bool
    {
        return $this->notifyRequesterAdded;
    }
}
