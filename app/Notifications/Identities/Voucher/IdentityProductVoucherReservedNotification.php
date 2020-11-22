<?php

namespace App\Notifications\Identities\Voucher;

use App\Mail\Vouchers\ProductReservedMail;
use App\Mail\Vouchers\ProductReservedUserMail;
use App\Models\Implementation;
use App\Models\Voucher;
use App\Services\Forus\Identity\Models\Identity;

class IdentityProductVoucherReservedNotification extends BaseIdentityVoucherNotification
{
    protected $key = 'notifications_identities.product_voucher_reserved';
    protected $sendMail = true;

    /**
     * @param Identity $identity
     * @throws \Exception
     */
    public function toMail(Identity $identity): void
    {
        /** @var Voucher $voucher */
        $voucher = $this->eventLog->loggable;

        notification_service()->sendMailNotification(
            $identity->primary_email->email,
            new ProductReservedUserMail(array_merge($this->eventLog->data, [
                'provider_organization_name' => $this->eventLog->data['provider_name'],
                'qr_token'  => $voucher->token_without_confirmation->address,
                'expire_at_locale' => $this->eventLog->data['voucher_expire_date_locale'],
            ]), Implementation::emailFrom($this->eventLog->data['implementation_key']))
        );

        notification_service()->sendMailNotification(
            $identity->primary_email->email,
            new ProductReservedMail(array_merge(
                $this->eventLog->data, [
                    'expiration_date' => $this->eventLog->data['voucher_expire_date_locale']
                ]
            ), Implementation::emailFrom())
        );
    }
}
