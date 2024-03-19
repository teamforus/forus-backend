<?php

namespace App\Events\Vouchers;

use App\Models\Voucher;

class VoucherSendToEmailEvent extends BaseVoucherEvent
{
    protected $email;

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }
}
