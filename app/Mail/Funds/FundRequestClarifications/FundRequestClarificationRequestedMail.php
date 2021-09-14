<?php

namespace App\Mail\Funds\FundRequestClarifications;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

/**
 * Class FundRequestCreatedMail
 * @package App\Mail\FundRequests
 */
class FundRequestClarificationRequestedMail extends ImplementationMail
{
    protected $notificationTemplateKey = "notifications_identities.fund_request_feedback_requested";

    public function build(): Mailable
    {
        $linkTitle = $this->informalCommunication ? 'Ga naar je aanvraag' : 'Ga naar uw aanvraag';

        return $this->buildTemplatedNotification([
            'webshop_clarification_link' => $this->makeButton($this->mailData['webshop_clarification_link'], $linkTitle),
        ]);
    }
}
