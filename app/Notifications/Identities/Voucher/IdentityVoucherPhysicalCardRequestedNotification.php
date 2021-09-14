<?php

namespace App\Notifications\Identities\Voucher;

use App\Models\Voucher;
use App\Services\Forus\Identity\Models\Identity;
use App\Mail\Vouchers\RequestPhysicalCardMail;

class IdentityVoucherPhysicalCardRequestedNotification extends BaseIdentityVoucherNotification
{
    protected static $key = 'notifications_identities.voucher_physical_card_requested';
    protected static $scope = null;
    protected static $sendMail = true;

    protected static $visible = true;
    protected static $editable = true;

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var Voucher $voucher */
        $voucher = $this->eventLog->loggable;
        $mailable = new RequestPhysicalCardMail($this->eventLog->data, $voucher->fund->getEmailFrom());

        $this->sendMailNotification($identity->email, $mailable);
    }
}
