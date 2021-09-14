<?php

namespace App\Mail\Funds\FundRequestRecords;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

/**
 * Class FundRequestCreatedMail
 * @package App\Mail\FundRequests
 */
class FundRequestRecordDeclinedMail extends ImplementationMail
{
    protected $notificationTemplateKey = "notifications_identities.fund_request_record_declined";

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildTemplatedNotification([
            'webshop_link' => $this->makeLink($this->mailData['webshop_link'], $this->mailData['webshop_link']),
        ]);
    }
}
