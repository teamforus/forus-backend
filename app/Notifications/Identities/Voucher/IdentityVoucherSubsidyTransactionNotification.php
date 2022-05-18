<?php

namespace App\Notifications\Identities\Voucher;

use App\Mail\Vouchers\PaymentSuccessSubsidyMail;
use App\Models\Voucher;
use App\Services\Forus\Identity\Models\Identity;

/**
 * New budget subsidy transaction created
 */
class IdentityVoucherSubsidyTransactionNotification extends BaseIdentityVoucherNotification
{
    protected static ?string $key = 'notifications_identities.voucher_subsidy_transaction';
    protected static ?string $pushKey = "voucher.transaction";

    /**
     * @param Identity $identity
     * @return bool|void
     */
    public function toMail(Identity $identity): void
    {
        /** @var Voucher $voucher */
        $voucher = $this->eventLog->loggable;

        $this->sendMailNotification(
            $identity->primary_email->email,
            new PaymentSuccessSubsidyMail(array_merge($this->eventLog->data, [
                'webshop_link' => $voucher->fund->urlWebshop(),
            ]), $voucher->fund->fund_config->implementation->getEmailFrom())
        );
    }
}
