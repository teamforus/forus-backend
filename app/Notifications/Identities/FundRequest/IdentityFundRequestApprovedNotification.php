<?php

namespace App\Notifications\Identities\FundRequest;

use App\Mail\Funds\FundRequests\FundRequestApprovedMail;
use App\Models\FundRequest;
use App\Models\Identity;

class IdentityFundRequestApprovedNotification extends BaseIdentityFundRequestNotification
{
    protected static ?string $key = 'notifications_identities.fund_request_approved';

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var FundRequest $fundRequest */
        $fundRequest = $this->eventLog->loggable;

        $mailable = new FundRequestApprovedMail([
            ...$this->eventLog->data,
            'app_link' => 'https://www.forus.io/DL',
            'webshop_link' => $fundRequest->fund->urlWebshop('/funds'),
        ], $fundRequest->fund->getEmailFrom());

        $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
    }
}
