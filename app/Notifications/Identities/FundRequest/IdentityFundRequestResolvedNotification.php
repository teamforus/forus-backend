<?php

namespace App\Notifications\Identities\FundRequest;

use App\Mail\Funds\FundRequests\FundRequestApprovedMail;
use App\Mail\Funds\FundRequests\FundRequestDeniedMail;
use App\Models\FundRequest;
use App\Services\Forus\Identity\Models\Identity;

/**
 * Class IdentityFundRequestResolvedNotification
 * @package App\Notifications\Identities\FundRequest
 */
class IdentityFundRequestResolvedNotification extends BaseIdentityFundRequestNotification
{
    protected $key = 'notifications_identities.fund_request_resolved';
    protected $sendMail = true;

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var FundRequest $fundRequest */
        $fundRequest = $this->eventLog->loggable;

        if ($fundRequest->state === FundRequest::STATE_APPROVED) {
            $mailable = new FundRequestApprovedMail(
                $this->eventLog->data['fund_name'],
                $fundRequest->fund->urlWebshop('/funds'),
                'https://www.forus.io/DL',
                $fundRequest->fund->getEmailFrom()
            );
        } else {
            $mailable = new FundRequestDeniedMail(
                $this->eventLog->data['fund_name'],
                $this->eventLog->data['fund_request_note'],
                $this->eventLog->data['sponsor_name'],
                $this->eventLog->data['sponsor_phone'],
                $this->eventLog->data['sponsor_email'],
                $fundRequest->fund->getEmailFrom()
            );
        }

        $this->getNotificationService()->sendMailNotification($identity->email, $mailable);
    }
}
