<?php

namespace App\Notifications\Identities\Voucher;

use App\Mail\Vouchers\SendProductVoucherMail;
use App\Models\Identity;
use App\Models\Implementation;

/**
 * Send voucher to owner's email.
 */
class IdentityProductVoucherSharedByEmailNotification extends BaseIdentityVoucherNotification
{
    protected static ?string $key = 'notifications_identities.product_voucher_shared_by_email';
    protected static ?string $scope = null;

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        $mailable = new SendProductVoucherMail($this->eventLog->data, Implementation::emailFrom());
        $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
    }
}
