<?php

namespace App\Notifications\Identities\FundRequest;

use App\Mail\Funds\FundRequests\FundRequestDeniedMail;
use App\Models\FundRequest;
use App\Services\Forus\Identity\Models\Identity;

/**
 * Class IdentityFundRequestResolvedNotification
 * @package App\Notifications\Identities\FundRequest
 */
class IdentityFundRequestDeniedNotification extends BaseIdentityFundRequestNotification
{
    protected static $key = 'notifications_identities.fund_request_denied';

    protected static $visible = true;
    protected static $editable = true;

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var FundRequest $fundRequest */
        $fundRequest = $this->eventLog->loggable;
        $emailFrom = $fundRequest->fund->getEmailFrom();
        $mailable = new FundRequestDeniedMail($this->eventLog->data, $emailFrom);

        $this->sendMailNotification($identity->email, $mailable);
    }
}
