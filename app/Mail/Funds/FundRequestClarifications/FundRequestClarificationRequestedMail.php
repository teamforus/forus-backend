<?php

namespace App\Mail\Funds\FundRequestClarifications;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

/**
 * Notify requester about fund request clarification being requested by the sponsor/validator
 */
class FundRequestClarificationRequestedMail extends ImplementationMail
{
    protected $notificationTemplateKey = "notifications_identities.fund_request_feedback_requested";

    public function build(): Mailable
    {
        $linkTitle = $this->informalCommunication ? 'Ga naar je aanvraag' : 'Ga naar uw aanvraag';
        $question = $this->mailData['fund_request_clarification_question'];

        return $this->buildTemplatedNotification([
            'fund_request_clarification_question' => nl2br(e($question)),
            'webshop_clarification_link' => $this->makeButton($this->mailData['webshop_clarification_link'], $linkTitle),
        ]);
    }
}
