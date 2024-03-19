<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class RequestPhysicalCardMail extends ImplementationMail
{
    protected string $notificationTemplateKey = 'notifications_identities.voucher_physical_card_requested';

    /**
     * @throws CommonMarkException
     */
    public function build(): Mailable|null
    {
        return $this->buildNotificationTemplatedMail();
    }
}
