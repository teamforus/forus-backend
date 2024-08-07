<?php

namespace App\Notifications\Identities\FundRequest;

use App\Mail\Funds\FundRequests\FundRequestCreatedMail;
use App\Models\FundRequest;
use App\Models\Identity;

class IdentityFundRequestCreatedNotification extends BaseIdentityFundRequestNotification
{
    protected static ?string $key = 'notifications_identities.fund_request_created';

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var FundRequest $fundRequest */
        $fundRequest = $this->eventLog->loggable;
        $fund = $fundRequest->fund;

        if ($fundRequest->state !== $fundRequest::STATE_PENDING) {
            return;
        }

        $mailable = new FundRequestCreatedMail([
            ...$this->eventLog->data,
            'webshop_link' => $fund->urlWebshop(),
        ], $fund->getEmailFrom());

        $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
    }
}
