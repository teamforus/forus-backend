<?php

namespace App\Mail\Funds\FundRequests;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

/**
 * Class FundRequestDeniedMail
 * @package App\Mail\Funds\FundRequests
 */
class FundRequestDeniedMail extends ImplementationMail
{
    protected $notificationTemplateKey = 'notifications_identities.fund_request_denied';

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildTemplatedNotification();
    }
}
