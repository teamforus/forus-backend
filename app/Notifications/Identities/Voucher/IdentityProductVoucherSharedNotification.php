<?php

namespace App\Notifications\Identities\Voucher;

use App\Mail\Vouchers\ShareProductVoucherMail;
use App\Models\Voucher;
use App\Models\VoucherToken;
use App\Services\Forus\Identity\Models\Identity;

class IdentityProductVoucherSharedNotification extends BaseIdentityVoucherNotification
{
    protected $key = 'notifications_identities.product_voucher_shared';

    public function via(): array
    {
        return [
            'mail'
        ];
    }

    /**
     * @param Identity $identity
     * @return bool|null
     * @throws \Exception
     */
    public function toMail(Identity $identity)
    {
        /** @var Voucher $voucher */
        $voucher = $this->eventLog->loggable;

        /** @var VoucherToken $voucherToken */
        $voucherToken = $voucher->tokens()->where([
            'need_confirmation' => false
        ])->first();

        return resolve('forus.services.notification')->sendMailNotification(
            $identity->primary_email->email,
            new ShareProductVoucherMail(array_merge($this->eventLog->data, [
                'reason'   => $this->eventLog->data['voucher_share_message'],
                'qr_token' => $voucherToken->address,
                'requester_email' => $identity->primary_email->email
            ]), $voucher->fund->fund_config->implementation->getEmailFrom())
        );
    }
}
