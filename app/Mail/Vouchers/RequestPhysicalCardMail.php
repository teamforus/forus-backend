<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

/**
 * Class RequestPhysicalCardMail
 * @package App\Mail\Vouchers
 */
class RequestPhysicalCardMail extends ImplementationMail
{
    protected $notificationTemplateKey = 'notifications_identities.voucher_physical_card_requested';

    public function build(): Mailable
    {
        return $this->buildTemplatedNotification();
    }
}
