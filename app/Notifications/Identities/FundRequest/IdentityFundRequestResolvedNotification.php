<?php

namespace App\Notifications\Identities\FundRequest;

use App\Mail\Funds\FundRequests\FundRequestResolvedMail;
use App\Models\FundRequest;
use App\Services\Forus\Identity\Models\Identity;

class IdentityFundRequestResolvedNotification extends BaseIdentityFundRequestNotification
{
    protected $key = 'notifications_identities.fund_request_resolved';
    protected $sendMail = true;

    public function toMail(Identity $identity): void
    {
        /** @var FundRequest $fundRequest */
        $fundRequest = $this->eventLog->loggable;

        resolve('forus.services.notification')->sendMailNotification(
            $identity->email,
            new FundRequestResolvedMail(
                $this->eventLog->data['fund_request_state'],
                $this->eventLog->data['fund_name'],
                $fundRequest->fund->urlWebshop(),
                $fundRequest->fund->getEmailFrom()
            )
        );
    }
}
