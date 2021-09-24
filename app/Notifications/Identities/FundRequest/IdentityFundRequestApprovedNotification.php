<?php

namespace App\Notifications\Identities\FundRequest;

use App\Mail\Funds\FundRequests\FundRequestApprovedMail;
use App\Models\FundRequest;
use App\Services\Forus\Identity\Models\Identity;

/**
 * Class IdentityFundRequestResolvedNotification
 * @package App\Notifications\Identities\FundRequest
 */
class IdentityFundRequestApprovedNotification extends BaseIdentityFundRequestNotification
{
    protected static $key = 'notifications_identities.fund_request_approved';

    protected static $visible = true;
    protected static $editable = true;

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var FundRequest $fundRequest */
        $fundRequest = $this->eventLog->loggable;

        $mailable = new FundRequestApprovedMail(array_merge($this->eventLog->data, [
            'app_link'      => 'https://www.forus.io/DL',
            'webshop_link'  => $fundRequest->fund->urlWebshop('/funds'),
        ]), $fundRequest->fund->getEmailFrom());

        $this->sendMailNotification($identity->email, $mailable);
    }
}
