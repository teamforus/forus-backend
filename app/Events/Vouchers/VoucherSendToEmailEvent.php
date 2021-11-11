<?php

namespace App\Events\Vouchers;

use App\Models\Voucher;

class VoucherSendToEmailEvent extends BaseVoucherEvent
{
    protected $email;

    /**
     * Create a new event instance.
     *
     * @param Voucher $voucher
     * @param string $email
     */
    public function __construct(Voucher $voucher, string $email)
    {
        parent::__construct($voucher);

        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }
}
