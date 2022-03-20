<?php

namespace App\Notifications\Identities\FundRequest;

use App\Mail\Funds\FundRequests\FundRequestDisregardedMail;
use App\Models\FundRequest;
use App\Services\Forus\Identity\Models\Identity;

class IdentityFundRequestDisregardedNotification extends BaseIdentityFundRequestNotification
{
    protected static $key = 'notifications_identities.fund_request_disregarded';

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var FundRequest $fundRequest */
        $fundRequest = $this->eventLog->loggable;

        $mailable = new FundRequestDisregardedMail($this->eventLog->data, $fundRequest->fund->getEmailFrom());

        $this->sendMailNotification($identity->email, $mailable);
    }
}