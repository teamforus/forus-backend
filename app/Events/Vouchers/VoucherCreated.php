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
