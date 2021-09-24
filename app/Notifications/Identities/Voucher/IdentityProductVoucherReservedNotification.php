<?php

namespace App\Notifications\Identities\Voucher;

use App\Mail\Vouchers\ProductReservedRequesterMail;
use App\Models\Implementation;
use App\Models\Voucher;
use App\Services\Forus\Identity\Models\Identity;

/**
 * Product reservation (legacy reservation) was reserved
 */
class IdentityProductVoucherReservedNotification extends BaseIdentityVoucherNotification
{
    protected static $key = 'notifications_identities.product_voucher_reserved';
    protected static $visible = true;

    /**
     * @param Identity $identity
     * @throws \Exception
     */
    public function toMail(Identity $identity): void
    {
        /** @var Voucher $voucher */
        $voucher = $this->eventLog->loggable;

        $this->sendMailNotification(
            $identity->primary_email->email,
            new ProductReservedRequesterMail(array_merge($this->eventLog->data, [
                'qr_token'  => $voucher->token_without_confirmation->address,
            ]), Implementation::emailFrom($this->eventLog->data['implementation_key']))
        );
    }
}
