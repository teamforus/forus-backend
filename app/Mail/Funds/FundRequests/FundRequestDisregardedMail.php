<?php

namespace App\Mail\Funds\FundRequests;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class FundRequestDisregardedMail extends ImplementationMail
{
    public ?string $notificationTemplateKey = 'notifications_identities.fund_request_disregarded';

    /**
     * @throws CommonMarkException
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildNotificationTemplatedMail();
    }
}
