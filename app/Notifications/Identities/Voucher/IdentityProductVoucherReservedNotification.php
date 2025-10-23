<?php

namespace App\Notifications\Identities\Voucher;

use App\Mail\Vouchers\ProductReservedRequesterMail;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Voucher;
use Exception;

/**
 * Product reservation (legacy reservation) was reserved.
 */
class IdentityProductVoucherReservedNotification extends BaseIdentityVoucherNotification
{
    protected static ?string $key = 'notifications_identities.product_voucher_reserved';

    /**
     * @param Identity $identity
     * @throws Exception
     */
    public function toMail(Identity $identity): void
    {
        /** @var Voucher $voucher */
        $voucher = $this->eventLog->loggable;

        $qr_token = $voucher->fund->fund_config->show_qr_code
            ? $voucher->token_without_confirmation->address
            : null;

        $mailable = new ProductReservedRequesterMail([
            ...$this->eventLog->data,
            ...compact('qr_token'),
        ], Implementation::emailFrom($this->eventLog->data['implementation_key']));

        $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
    }
}
