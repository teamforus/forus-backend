<?php

namespace App\Notifications\Identities\FundRequest;

use App\Mail\Funds\FundRequestClarifications\FundRequestClarificationRequestedMail;
use App\Models\FundRequest;
use App\Services\Forus\Identity\Models\Identity;

/**
 * Class IdentityFundRequestFeedbackRequestedNotification
 * @package App\Notifications\Identities\FundRequest
 */
class IdentityFundRequestFeedbackRequestedNotification extends BaseIdentityFundRequestNotification
{
    protected $key = 'notifications_identities.fund_request_feedback_requested';
    protected $sendMail = true;

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var FundRequest $fundRequest */
        $fundRequest = $this->eventLog->loggable;
        $fund = $fundRequest->fund;

        $this->getNotificationService()->sendMailNotification(
            $identity->primary_email->email,
            new FundRequestClarificationRequestedMail(
                $this->eventLog->data['fund_name'],
                $this->eventLog->data['fund_request_clarification_question'],
                $fund->urlWebshop(sprintf(
                    'funds/%s/requests/%s/clarifications/%s',
                    $this->eventLog->data['fund_id'],
                    $this->eventLog->data['fund_request_id'],
                    $this->eventLog->data['fund_request_clarification_id']
                )),
                $fund->urlWebshop(),
                $fund->getEmailFrom()
            )
        );
    }
}
