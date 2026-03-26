<?php

namespace App\Notifications\Identities\Voucher;

use App\Mail\Vouchers\SendVoucherMail;
use App\Models\Identity;
use App\Models\Implementation;

/**
 * Send voucher to owner's email.
 */
class IdentityVoucherSharedByEmailNotification extends BaseIdentityVoucherNotification
{
    protected static ?string $key = 'notifications_identities.voucher_shared_by_email';
    protected static ?string $scope = null;

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        $mailable = new SendVoucherMail($this->eventLog->data, Implementation::emailFrom());
        $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
    }
}
