<?php

namespace App\Events\Vouchers;

use App\Models\Voucher;

class ProductVoucherShared extends BaseVoucherEvent
{
    protected $message;
    protected $sendCopyToUser;

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
