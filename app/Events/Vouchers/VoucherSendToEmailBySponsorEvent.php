<?php

namespace App\Events\Vouchers;

use App\Models\Voucher;

class VoucherSendToEmailBySponsorEvent extends BaseVoucherEvent
{
    protected ?string $email;

    /**
     * Create a new event instance.
     *
     * @param Voucher $voucher
     * @param string|null $email
     */
    public function __construct(Voucher $voucher, ?string $email)
    {
        parent::__construct($voucher);

        $this->email = $email;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }
}
