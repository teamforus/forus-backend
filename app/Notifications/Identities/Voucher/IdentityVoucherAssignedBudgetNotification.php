<?php

namespace App\Notifications\Identities\Voucher;

use App\Mail\Vouchers\VoucherAssignedBudgetMail;
use App\Models\Identity;
use App\Models\Voucher;

/**
 * Budget voucher was assigned to identity.
 */
class IdentityVoucherAssignedBudgetNotification extends BaseIdentityVoucherNotification
{
    protected static ?string $key = 'notifications_identities.identity_voucher_assigned_budget';
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

        $mailable = new VoucherAssignedBudgetMail([
            ...$this->eventLog->data,
            'qr_token' => $voucher->token_without_confirmation->address,
            'webshop_link' => $voucher->fund->urlWebshop(),
        ], $voucher->fund->getEmailFrom());

        $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
    }
}
