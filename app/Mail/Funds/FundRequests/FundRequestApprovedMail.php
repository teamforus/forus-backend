<?php

namespace App\Mail\Funds\FundRequests;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

class FundRequestApprovedMail extends ImplementationMail
{
    protected $notificationTemplateKey = "notifications_identities.fund_request_approved";

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildTemplatedNotification([
            'app_link' => $this->makeLink($this->mailData['app_link'], 'download de Me-app'),
            'webshop_link' => $this->makeLink($this->mailData['webshop_link'], 'hier'),
            'webshop_button' => $this->makeButton($this->mailData['webshop_link'], 'Activeer tegoed'),
        ]);
    }
}
