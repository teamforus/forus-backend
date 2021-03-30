<?php

namespace App\Notifications\Identities\FundRequest;

use App\Mail\Funds\FundRequests\FundRequestCreatedMail;
use App\Models\FundRequest;
use App\Services\Forus\Identity\Models\Identity;

/**
 * Class IdentityFundRequestCreatedNotification
 * @package App\Notifications\Identities\FundRequest
 */
class IdentityFundRequestCreatedNotification extends BaseIdentityFundRequestNotification
{
    protected $key = 'notifications_identities.fund_request_created';
    protected $sendMail = true;

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

        $this->getNotificationService()->sendMailNotification(
            $identity->primary_email->email,
            new FundRequestCreatedMail(
                $this->eventLog->data['fund_name'],
                $this->eventLog->data['sponsor_name'],
                $fund->urlWebshop(),
                $fund->getEmailFrom()
            )
        );
    }
}
