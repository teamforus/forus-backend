<?php

namespace App\Notifications\Identities\FundRequest;

use App\Mail\Funds\FundRequests\FundRequestDeniedMail;
use App\Models\FundRequest;
use App\Models\Identity;

class IdentityFundRequestDeniedNotification extends BaseIdentityFundRequestNotification
{
    protected static ?string $key = 'notifications_identities.fund_request_denied';

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var FundRequest $fundRequest */
        $fundRequest = $this->eventLog->loggable;
        $emailFrom = $fundRequest->fund->getEmailFrom();
        $mailable = new FundRequestDeniedMail($this->eventLog->data, $emailFrom);

        $this->sendMailNotification($identity->email, $mailable);
    }
}
