<?php

namespace App\Notifications\Identities\Voucher;

use App\Mail\Vouchers\DeactivationVoucherMail;
use App\Models\Voucher;
use App\Services\Forus\Identity\Models\Identity;

/**
 * The voucher was deactivated
 */
class IdentityVoucherDeactivatedNotification extends BaseIdentityVoucherNotification
{
    protected static $scope = null;
    protected static $key = 'notifications_identities.voucher_deactivated';

    public function toMail(Identity $identity): void
    {
        /** @var Voucher $voucher */
        $voucher = $this->eventLog->loggable;

        if ($this->eventLog->data['notify_by_email'] ?? false) {
            $this->sendMailNotification(
                $voucher->identity->primary_email->email,
                new DeactivationVoucherMail($this->eventLog->data, $voucher->fund->getEmailFrom())
            );
        }
    }
}
