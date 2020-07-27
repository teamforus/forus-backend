<?php

namespace App\Notifications\Identities\Voucher;

use App\Mail\Vouchers\ShareProductVoucherMail;
use App\Models\Voucher;
use App\Services\Forus\Identity\Models\Identity;

class IdentityProductVoucherSharedNotification extends BaseIdentityVoucherNotification
{
    protected $key = 'notifications_identities.product_voucher_shared';
    protected $sendMail = true;

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var Voucher $voucher */
        $voucher = $this->eventLog->loggable;

        if ($this->eventLog->data['voucher_share_send_copy'] ?? false) {
            notification_service()->sendMailNotification(
                $identity->primary_email->email,
                new ShareProductVoucherMail(array_merge($this->eventLog->data, [
                    'reason'   => $this->eventLog->data['voucher_share_message'] ?? '',
                    'qr_token' => $voucher->token_without_confirmation->address,
                    'requester_email' => $identity->primary_email->email
                ]), $voucher->fund->fund_config->implementation->getEmailFrom())
            );
        }

        notification_service()->sendMailNotification(
            $voucher->product->organization->email,
            new ShareProductVoucherMail(array_merge($this->eventLog->data, [
                'reason'   => $this->eventLog->data['voucher_share_message'] ?? '',
                'qr_token' => $voucher->token_without_confirmation->address,
                'requester_email' => $identity->primary_email->email
            ]), $voucher->fund->fund_config->implementation->getEmailFrom())
        );
    }
}
