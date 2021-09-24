<?php

namespace App\Notifications\Identities\Voucher;

use App\Mail\Vouchers\ShareProductVoucherMail;
use App\Models\Voucher;
use App\Services\Forus\Identity\Models\Identity;

/**
 * Share product voucher to the provider by email
 */
class IdentityProductVoucherSharedNotification extends BaseIdentityVoucherNotification
{
    protected static $key = 'notifications_identities.product_voucher_shared';
    protected static $visible = true;

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var Voucher $voucher */
        $voucher = $this->eventLog->loggable;

        if ($this->eventLog->data['voucher_share_send_copy'] ?? false) {
            $this->sendMailNotification(
                $identity->primary_email->email,
                new ShareProductVoucherMail(array_merge($this->eventLog->data, [
                    'reason'   => $this->eventLog->data['voucher_share_message'] ?? '',
                    'qr_token' => $voucher->token_without_confirmation->address,
                    'requester_email' => $identity->primary_email->email
                ]), $voucher->fund->fund_config->implementation->getEmailFrom())
            );
        }

        $this->sendMailNotification(
            $voucher->product->organization->email,
            new ShareProductVoucherMail(array_merge($this->eventLog->data, [
                'reason'   => $this->eventLog->data['voucher_share_message'] ?? '',
                'qr_token' => $voucher->token_without_confirmation->address,
                'requester_email' => $identity->primary_email->email
            ]), $voucher->fund->fund_config->implementation->getEmailFrom())
        );
    }
}
