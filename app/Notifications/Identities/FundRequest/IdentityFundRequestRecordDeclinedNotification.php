<?php

namespace App\Notifications\Identities\FundRequest;

use App\Mail\Funds\FundRequestRecords\FundRequestRecordDeclinedMail;
use App\Models\FundRequest;
use App\Services\Forus\Identity\Models\Identity;

/**
 * Class IdentityFundRequestResolvedNotification
 * @package App\Notifications\Identities\FundRequest
 */
class IdentityFundRequestRecordDeclinedNotification extends BaseIdentityFundRequestNotification
{
    protected static $key = 'notifications_identities.fund_request_record_declined';
    protected static $scope = null;
    protected static $visible = true;

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var FundRequest $fundRequest */
        $fundRequest = $this->eventLog->loggable;
        $fund = $fundRequest->fund;

        $mailable = new FundRequestRecordDeclinedMail(array_merge($this->eventLog->data, [
            'webshop_link' => $fundRequest->fund->urlWebshop(),
        ]), $fund->getEmailFrom());

        $this->sendMailNotification($identity->email, $mailable);
    }
}
