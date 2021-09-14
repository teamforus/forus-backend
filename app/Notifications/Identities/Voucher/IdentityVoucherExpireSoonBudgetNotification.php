<?php

namespace App\Notifications\Identities\Voucher;

use App\Mail\Funds\FundExpireSoonMail;
use App\Models\Voucher;
use App\Services\Forus\Identity\Models\Identity;

class IdentityVoucherExpireSoonBudgetNotification extends BaseIdentityVoucherNotification
{
    protected static $key = 'notifications_identities.voucher_expire_soon_budget';
    protected static $sendMail = true;

    protected static $visible = true;
    protected static $editable = true;

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var Voucher $voucher */
        $voucher = $this->eventLog->loggable;

        if ($voucher->amount_available > 0) {
            $mailable = new FundExpireSoonMail(array_merge($this->eventLog->data, [
                'link_webshop' => $voucher->fund->urlWebshop(),
            ]), $voucher->fund->getEmailFrom());

            $this->sendMailNotification($voucher->identity->email, $mailable);
        }
    }
}
