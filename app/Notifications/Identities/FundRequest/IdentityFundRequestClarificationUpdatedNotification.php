<?php

namespace App\Notifications\Identities\FundRequest;

use App\Mail\Funds\FundRequestClarifications\FundRequestClarificationUpdatedMail;
use App\Models\FundRequestRecord;
use App\Models\Identity;

class IdentityFundRequestClarificationUpdatedNotification extends BaseIdentityFundRequestRecordNotification
{
    protected static ?string $key = 'notifications_identities.fund_request_clarification_updated';

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var FundRequestRecord $fundRequestRecord */
        $fundRequestRecord = $this->eventLog->loggable;
        $fundRequest = $fundRequestRecord->fund_request;

        $linkClarification = $fundRequest->fund->urlWebshop(sprintf(
            'fund-request/%s',
            $this->eventLog->data['fund_request_id'],
        ));

        $mailable = new FundRequestClarificationUpdatedMail([
            ...$this->eventLog->data,
            'webshop_clarification_link' => $linkClarification,
        ], $fundRequest->fund->getEmailFrom());

        $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
    }
}
