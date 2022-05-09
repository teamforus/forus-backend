<?php

namespace App\Notifications\Identities\Voucher;

use App\Mail\Vouchers\VoucherAssignedSubsidyMail;
use App\Services\Forus\Identity\Models\Identity;
use App\Models\Voucher;

/**
 * Subsidy voucher was assigned to identity
 */
class IdentityVoucherAssignedSubsidyNotification extends BaseIdentityVoucherNotification
{
    protected static ?string $key = 'notifications_identities.identity_voucher_assigned_subsidy';
    protected static ?string $pushKey = 'voucher.assigned';

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var Voucher $voucher */
        $voucher = $this->eventLog->loggable;

        if ($voucher->expired) {
            return;
        }

        $mailable = new VoucherAssignedSubsidyMail(array_merge($this->eventLog->data, [
            'qr_token' => $voucher->token_without_confirmation->address,
            'webshop_link' => $voucher->fund->urlWebshop(),
        ]), $voucher->fund->getEmailFrom());

        $this->sendMailNotification($identity->email, $mailable);
    }
}
