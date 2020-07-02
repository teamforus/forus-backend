<?php

namespace App\Notifications\Identities\Voucher;

use App\Mail\Vouchers\PaymentSuccessMail;
use App\Models\Voucher;
use App\Services\Forus\Identity\Models\Identity;

class IdentityVoucherTransactionNotification extends BaseIdentityVoucherNotification
{
    protected $key = 'notifications_identities.voucher_transaction';

    /**
     * @param Identity $identity
     * @return bool|null
     * @throws \Exception
     */
    public function toMail(Identity $identity)
    {
        /** @var Voucher $voucher */
        $voucher = $this->eventLog->loggable;

        return resolve('forus.services.notification')->sendMailNotification(
            $identity->primary_email->email,
            new PaymentSuccessMail(array_merge($this->eventLog->data, [
                'current_budget' => $voucher->parent ?
                    $voucher->parent->amount_available : $voucher->amount_available,
            ]), $voucher->fund->fund_config->implementation->getEmailFrom())
        );
    }
}
