<?php

namespace App\Notifications\Identities\FundRequest;

use App\Mail\Funds\FundRequestRecords\FundRequestRecordDeclinedMail;
use App\Models\FundRequestRecord;
use App\Models\Identity;

class IdentityFundRequestRecordDeclinedNotification extends BaseIdentityFundRequestRecordNotification
{
    protected static ?string $key = 'notifications_identities.fund_request_record_declined';
    protected static ?string $scope = null;

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var FundRequestRecord $fundRequestRecord */
        $fundRequestRecord = $this->eventLog->loggable;
        $fundRequest = $fundRequestRecord->fund_request;

        if (empty($this->eventLog->data['rejection_note'])) {
            return;
        }

        $mailable = new FundRequestRecordDeclinedMail(array_merge($this->eventLog->data, [
            'webshop_link' => $fundRequest->fund->urlWebshop(),
        ]), $fundRequest->fund->getEmailFrom());

        $this->sendMailNotification($identity->email, $mailable);
    }
}
