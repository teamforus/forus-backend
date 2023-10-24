<?php

namespace App\Notifications\Identities\FundRequest;

use App\Mail\Funds\FundRequestClarifications\FundRequestClarificationRequestedMail;
use App\Models\FundRequestRecord;
use App\Models\Identity;

class IdentityFundRequestRecordFeedbackRequestedNotification extends BaseIdentityFundRequestRecordNotification
{
    protected static ?string $key = 'notifications_identities.fund_request_feedback_requested';

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

        $this->sendMailNotification(
            $identity->email,
            new FundRequestClarificationRequestedMail(array_merge($this->eventLog->data, [
                'webshop_clarification_link' => $linkClarification,
            ]), $fundRequest->fund->getEmailFrom())
        );
    }
}
