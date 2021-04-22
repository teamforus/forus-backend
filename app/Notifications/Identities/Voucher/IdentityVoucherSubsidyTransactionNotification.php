<?php

namespace App\Notifications\Identities\Voucher;

use App\Mail\Vouchers\PaymentSuccessMail;
use App\Models\Voucher;
use App\Services\Forus\Identity\Models\Identity;

/**
 * Class IdentityVoucherSubsidyTransactionNotification
 * @package App\Notifications\Identities\Voucher
 */
class IdentityVoucherSubsidyTransactionNotification extends BaseIdentityVoucherNotification
{
    protected $key = 'notifications_identities.voucher_subsidy_transaction';
    protected $sendMail = true;

    /**
     * @param Identity $identity
     * @return bool|void
     */
    public function toMail(Identity $identity): void
    {
        /** @var Voucher $voucher */
        $voucher = $this->eventLog->loggable;

        $this->getNotificationService()->sendMailNotification(
            $identity->primary_email->email,
            new PaymentSuccessMail(array_merge($this->eventLog->data, [
                'webshop_link' => $voucher->fund->urlWebshop(),
            ]), $voucher->fund->fund_config->implementation->getEmailFrom())
        );
    }
}
