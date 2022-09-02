<?php

namespace App\Notifications\Identities\Voucher;

use App\Mail\Vouchers\DeactivationVoucherMail;
use App\Models\Voucher;
use App\Models\Identity;

/**
 * The voucher was deactivated
 */
class IdentityVoucherDeactivatedNotification extends BaseIdentityVoucherNotification
{
    protected static ?string $scope = null;
    protected static ?string $key = 'notifications_identities.voucher_deactivated';

    public function toMail(Identity $identity): void
    {
        /** @var Voucher $voucher */
        $voucher = $this->eventLog->loggable;

        if ($this->eventLog->data['notify_by_email'] ?? false) {
            $this->sendMailNotification(
                $voucher->identity->email,
                new DeactivationVoucherMail($this->eventLog->data, $voucher->fund->getEmailFrom())
            );
        }
    }
}
