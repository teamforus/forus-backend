<?php

namespace App\Notifications\Identities\Voucher;

use App\Mail\Vouchers\PaymentSuccessBudgetMail;
use App\Models\Voucher;
use App\Services\Forus\Identity\Models\Identity;

/**
 * Class IdentityVoucherTransactionNotification
 * @package App\Notifications\Identities\Voucher
 */
class IdentityVoucherBudgetTransactionNotification extends BaseIdentityVoucherNotification
{
    protected static $key = 'notifications_identities.voucher_budget_transaction';
    protected static $pushKey = "voucher.transaction";
    protected static $sendMail = true;
    protected static $sendPush = true;

    protected static $visible = true;

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
            new PaymentSuccessBudgetMail(array_merge($this->eventLog->data, [
                'webshop_link' => $voucher->fund->urlWebshop(),
            ]), $voucher->fund->fund_config->implementation->getEmailFrom())
        );
    }
}
