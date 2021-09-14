<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

/**
 * Class ShareProductMail
 * @package App\Mail\Vouchers
 */
class ShareProductVoucherMail extends ImplementationMail
{
    protected $notificationTemplateKey = 'notifications_identities.product_voucher_shared';

    public function build(): Mailable
    {
        return $this->buildTemplatedNotification([
            'qr_token' => $this->makeQrCode($this->mailData['qr_token']),
        ]);
    }
}
