<?php

namespace App\Notifications\Identities\Voucher;

use App\Mail\Vouchers\SendProductVoucherMail;
use App\Models\Identity;
use App\Models\Voucher;

/**
 * Send voucher to owner's email.
 */
class IdentityProductVoucherSharedByEmailNotification extends BaseIdentityVoucherNotification
{
    protected static ?string $key = 'notifications_identities.product_voucher_shared_by_email';
    protected static ?string $scope = null;

    /**
     * @param Identity $identity
     * @return void
     */
    public function toMail(Identity $identity): void
    {
        /** @var Voucher $voucher */
        $voucher = $this->eventLog->loggable;

        $mailable = new SendProductVoucherMail($this->eventLog->data, $voucher->fund->getEmailFrom());
        $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
    }
}
