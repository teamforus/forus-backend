<?php

namespace App\Notifications\Identities\Voucher;

use App\Mail\Vouchers\VoucherExpireSoonBudgetMail;
use App\Models\Identity;
use App\Models\Voucher;

/**
 * The voucher will expire soon (budget/subsidy).
 */
class IdentityVoucherExpireSoonBudgetNotification extends BaseIdentityVoucherNotification
{
    protected static ?string $key = 'notifications_identities.voucher_expire_soon_budget';

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var Voucher $voucher */
        $voucher = $this->eventLog->loggable;

        if ($voucher->amount_available > 0) {
            $mailable = new VoucherExpireSoonBudgetMail([
                ...$this->eventLog->data,
                'webshop_link' => $voucher->fund->urlWebshop(),
            ], $voucher->fund->getEmailFrom());

            $this->sendMailNotification($voucher->identity->email, $mailable, $this->eventLog);
        }
    }
}
