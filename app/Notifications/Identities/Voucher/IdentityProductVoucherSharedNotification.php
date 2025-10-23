<?php

namespace App\Notifications\Identities\Voucher;

use App\Mail\Vouchers\ShareProductVoucherMail;
use App\Models\Identity;
use App\Models\Voucher;

/**
 * Share product voucher to the provider by email.
 */
class IdentityProductVoucherSharedNotification extends BaseIdentityVoucherNotification
{
    protected static ?string $key = 'notifications_identities.product_voucher_shared';

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var Voucher $voucher */
        $voucher = $this->eventLog->loggable;

        $qr_token = $voucher->fund->fund_config->show_qr_code
            ? $voucher->token_without_confirmation->address
            : null;

        if ($this->eventLog->data['voucher_share_send_copy'] ?? false) {
            $mailable = new ShareProductVoucherMail([
                ...$this->eventLog->data,
                ...compact('qr_token'),
                'reason' => $this->eventLog->data['voucher_share_message'] ?? '',
                'requester_email' => $identity->email,
            ], $voucher->fund->fund_config->implementation->getEmailFrom());

            $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
        }

        $mailable = new ShareProductVoucherMail([
            ...$this->eventLog->data,
            ...compact('qr_token'),
            'reason' => $this->eventLog->data['voucher_share_message'] ?? '',
            'requester_email' => $identity->email,
        ], $voucher->fund->fund_config->implementation->getEmailFrom());

        $this->sendMailNotification(
            $voucher->product->organization->email,
            $mailable,
            $this->eventLog,
        );
    }
}
