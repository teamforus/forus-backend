<?php

namespace App\Notifications\Organizations\Products;

use App\Mail\Vouchers\ProductReservedMail;
use App\Models\Implementation;
use App\Services\Forus\Identity\Models\Identity;

/**
 * Class ProductReservedNotification
 * @package App\Notifications\Organizations\Products
 */
class ProductReservedNotification extends BaseProductsNotification
{
    protected $sendMail = true;
    protected $key = 'notifications_products.reserved';
    protected static $permissions = [
        'manage_products'
    ];

    public function toMail(Identity $identity): void
    {
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