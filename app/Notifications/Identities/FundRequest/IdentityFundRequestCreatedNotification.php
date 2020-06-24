<?php

namespace App\Notifications\Identities\FundRequest;

use App\Mail\Funds\FundRequests\FundRequestCreatedMail;
use App\Models\FundRequest;
use App\Services\Forus\Identity\Models\Identity;

class IdentityFundRequestCreatedNotification extends BaseIdentityFundRequestNotification
{
    protected $key = 'notifications_identities.fund_request_created';
    protected $sendMail = true;

    /**
     * @param Identity $identity
     * @return void
     */
    public function toMail(Identity $identity)
    {
        /** @var FundRequest $fundRequest */
        $fundRequest = $this->eventLog->loggable;
        $fund = $fundRequest->fund;

        if ($fundRequest->state != $fundRequest::STATE_PENDING) {
            return;
        }

        resolve('forus.services.notification')->sendMailNotification(
            $identity->primary_email->email,
            new FundRequestCreatedMail(
                $this->eventLog->data['fund_name'],
                $fund->urlWebshop(),
                $fund->getEmailFrom()
            )
        );
    }
}
