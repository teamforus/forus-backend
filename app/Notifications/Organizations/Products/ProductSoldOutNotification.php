<?php

namespace App\Notifications\Organizations\Products;

use App\Mail\Vouchers\ProductSoldOutMail;
use App\Models\Implementation;
use App\Services\Forus\Identity\Models\Identity;

/**
 * Class ProductSoldOutNotification
 * @package App\Notifications\Organizations\Products
 */
class ProductSoldOutNotification extends BaseProductsNotification
{
    protected static $key = 'notifications_products.sold_out';
    protected static $permissions = 'manage_products';
    protected static $sendMail = true;

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        $emailFrom = Implementation::general()->getEmailFrom();
        $mailable = new ProductSoldOutMail(array_merge($this->eventLog->data, [
            'provider_dashboard_link' => Implementation::general()->urlProviderDashboard(),
        ]), $emailFrom);

        $this->sendMailNotification($identity->email, $mailable);
    }
}
