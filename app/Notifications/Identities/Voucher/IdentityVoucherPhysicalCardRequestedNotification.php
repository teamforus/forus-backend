<?php

namespace App\Notifications\Identities\Voucher;

use App\Mail\Vouchers\RequestPhysicalCardMail;
use App\Models\Identity;
use App\Models\Voucher;

/**
 * A new physical card request was submitted
 */
class IdentityVoucherPhysicalCardRequestedNotification extends BaseIdentityVoucherNotification
{
    protected static ?string $key = 'notifications_identities.voucher_physical_card_requested';
    protected static ?string $scope = null;

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var Voucher $voucher */
        $voucher = $this->eventLog->loggable;

        $mailable = new RequestPhysicalCardMail(
            $this->eventLog->data,
            $voucher->fund->getEmailFrom(),
        );

        $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
    }
}
