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
    protected static $key = 'notifications_identities.fund_request_created';
    protected static $sendMail = true;

    protected static $visible = true;
    protected static $editable = true;

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

        $this->sendMailNotification(
            $identity->primary_email->email,
            new FundRequestCreatedMail(array_merge($this->eventLog->data, [
                'webshop_link' => $fund->urlWebshop(),
            ]), $fund->getEmailFrom())
        );
    }
}
