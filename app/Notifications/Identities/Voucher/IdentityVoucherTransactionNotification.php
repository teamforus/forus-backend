<?php

namespace App\Notifications\Identities\Voucher;

use App\Mail\Vouchers\PaymentSuccessMail;
use App\Models\Voucher;
use App\Services\Forus\Identity\Models\Identity;

/**
 * Class IdentityVoucherTransactionNotification
 * @package App\Notifications\Identities\Voucher
 */
class IdentityVoucherTransactionNotification extends BaseIdentityVoucherNotification
{
    protected $key = 'notifications_identities.voucher_transaction';
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
                'current_budget' => $this->eventLog->data['voucher_amount_locale'],
            ]), $voucher->fund->fund_config->implementation->getEmailFrom())
        );
    }
}
