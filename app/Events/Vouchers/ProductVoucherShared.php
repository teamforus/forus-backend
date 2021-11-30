<?php

namespace App\Events\Vouchers;

use App\Models\Voucher;

class ProductVoucherShared extends BaseVoucherEvent
{
    protected $message;
    protected $sendCopyToUser;

    /**
     * Create a new event instance.
     *
     * ProductVoucherShared constructor.
     * @param Voucher $voucher
     * @param string $message
     * @param bool $sendCopyToUser
     */
    public function __construct(Voucher $voucher, string $message, bool $sendCopyToUser)
    {
        parent::__construct($voucher);

        $this->message = $message;
        $this->sendCopyToUser = $sendCopyToUser;
    }

    /**
     * Get the share message product
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return bool
     */
    public function shouldSendCopyToUser(): bool
    {
        return $this->sendCopyToUser;
    }
}
