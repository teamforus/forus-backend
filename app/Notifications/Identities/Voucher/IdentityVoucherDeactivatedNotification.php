<?php

namespace App\Notifications\Identities\Voucher;

use App\Mail\Vouchers\DeactivationVoucherMail;
use App\Models\Voucher;
use App\Services\Forus\Identity\Models\Identity;

class IdentityVoucherDeactivatedNotification extends BaseIdentityVoucherNotification
{
    // todo: show this notification somewhere?
    protected $scope = null;
    protected $key = 'notifications_identities.voucher_deactivated';
    protected $sendMail = true;

    public function toMail(Identity $identity): void
    {
        /** @var Voucher $voucher */
        $voucher = $this->eventLog->loggable;

        if ($this->eventLog->data['notify_by_email'] ?? false) {
            $this->getNotificationService()->sendMailNotification(
                $voucher->identity->primary_email->email,
                new DeactivationVoucherMail($this->eventLog)
            );
        }
    }
}
