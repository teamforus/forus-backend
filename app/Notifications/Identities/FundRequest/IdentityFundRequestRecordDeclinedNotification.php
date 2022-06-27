<?php

namespace App\Notifications\Identities\FundRequest;

use App\Mail\Funds\FundRequestRecords\FundRequestRecordDeclinedMail;
use App\Models\FundRequestRecord;
use App\Services\Forus\Identity\Models\Identity;

class IdentityFundRequestRecordDeclinedNotification extends BaseIdentityFundRequestRecordNotification
{
    protected static $key = 'notifications_identities.fund_request_record_declined';
    protected static $scope = null;

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var FundRequestRecord $fundRequestRecord */
        $fundRequestRecord = $this->eventLog->loggable;
        $fundRequest = $fundRequestRecord->fund_request;

        $mailable = new FundRequestRecordDeclinedMail(array_merge($this->eventLog->data, [
            'webshop_link' => $fundRequest->fund->urlWebshop(),
        ]), $fundRequest->fund->getEmailFrom());

        $this->sendMailNotification($identity->email, $mailable);
    }
}
