<?php

namespace App\Mail\Funds\FundRequests;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

/**
 * Class FundRequestCreatedMail
 * @package App\Mail\FundRequests
 */
class FundRequestCreatedMail extends ImplementationMail
{
    protected $notificationTemplateKey = "notifications_identities.fund_request_created";

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildTemplatedNotification([
            'webshop_button' => $this->makeButton($this->mailData['webshop_link'] ?? '', "Ga naar de webshop"),
        ]);
    }
}
