<?php

namespace App\Notifications\Organizations\Products;

use App\Mail\Vouchers\ProductReservedMail;
use App\Mail\Vouchers\ProductReservedUserMail;
use App\Models\Implementation;
use App\Services\Forus\Identity\Models\Identity;
use Carbon\Carbon;

/**
 * Class ProductReservedNotification
 * @package App\Notifications\Organizations\Products
 */
class ProductReservedNotification extends BaseProductsNotification {
    protected $key = 'notifications_products.reserved';
    protected static $permissions = [
        'manage_products'
    ];

    protected $sendMail = true;

    public function toMail(Identity $identity)
    {
        resolve('forus.services.notification')->sendMailNotification(
            $identity->primary_email->email,
            new ProductReservedMail(array_merge(
                $this->eventLog->data, [
                    'expiration_date' => $this->eventLog->data['voucher_expire_date_locale']
                ]
            ), Implementation::emailFrom())
        );

        resolve('forus.services.notification')->sendMailNotification(
            $identity->primary_email->email,
            new ProductReservedUserMail(array_merge(
                $this->eventLog->data, [
                    'provider_organization_name' => $this->eventLog->data['provider_name'],
                    'qr_token'  => $this->eventLog->data['voucher_token_address'],
                    'expire_at' => format_date_locale(
                        $this->eventLog->data['voucher_expire_date']
                    ),
                    'expire_at_minus_1_day' => format_date_locale(
                        Carbon::parse($this->eventLog->data['voucher_expire_date'])->subDay()
                    )
                ]
            ), Implementation::emailFrom())
        );
    }
}
