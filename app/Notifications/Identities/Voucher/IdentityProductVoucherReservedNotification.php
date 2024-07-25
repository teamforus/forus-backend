<?php

namespace App\Notifications\Identities\Voucher;

use App\Mail\Vouchers\ProductReservedRequesterMail;
use App\Models\Implementation;
use App\Models\Voucher;
use App\Models\Identity;

/**
 * Product reservation (legacy reservation) was reserved
 */
class IdentityProductVoucherReservedNotification extends BaseIdentityVoucherNotification
{
    protected static ?string $key = 'notifications_identities.product_voucher_reserved';

    /**
     * @param Identity $identity
     * @throws \Exception
     */
    public function toMail(Identity $identity): void
    {
        /** @var Voucher $voucher */
        $voucher = $this->eventLog->loggable;

        $mailable = new ProductReservedRequesterMail([
            ...$this->eventLog->data,
            'qr_token'  => $voucher->token_without_confirmation->address,
        ], Implementation::emailFrom($this->eventLog->data['implementation_key']));

        $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
    }
}
