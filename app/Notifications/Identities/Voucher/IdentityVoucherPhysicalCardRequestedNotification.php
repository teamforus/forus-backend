<?php

namespace App\Notifications\Identities\Voucher;

use App\Models\Voucher;
use App\Services\Forus\Identity\Models\Identity;
use App\Mail\Vouchers\RequestPhysicalCardMail;

/**
 * A new physical card request was submitted
 */
class IdentityVoucherPhysicalCardRequestedNotification extends BaseIdentityVoucherNotification
{
    protected static $key = 'notifications_identities.voucher_physical_card_requested';
    protected static $scope = null;

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
