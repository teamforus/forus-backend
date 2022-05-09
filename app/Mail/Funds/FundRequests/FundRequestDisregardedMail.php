<?php

namespace App\Mail\Funds\FundRequests;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

/**
 * Class FundRequestDisregardedMail
 * @package App\Mail\Funds\FundRequests
 */
class FundRequestDisregardedMail extends ImplementationMail
{
    protected string $notificationTemplateKey = 'notifications_identities.fund_request_disregarded';

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildNotificationTemplatedMail();
    }
}