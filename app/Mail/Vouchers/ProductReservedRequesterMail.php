<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

/**
 * Class ProductReservedUserMail
 * @package App\Mail\Vouchers
 */
class ProductReservedRequesterMail extends ImplementationMail
{
    protected $notificationTemplateKey = 'notifications_identities.product_voucher_reserved';

    public function build(): Mailable
    {
        return $this->buildTemplatedNotification([
            'qr_token' => $this->makeQrCode($this->mailData['qr_token']),
        ]);
    }
}
