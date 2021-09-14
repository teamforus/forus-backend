<?php

namespace App\Notifications\Organizations\Products;

use App\Mail\Vouchers\ProductBoughtProviderMail;
use App\Models\Implementation;
use App\Services\Forus\Identity\Models\Identity;

/**
 * Class ProductReservedNotification
 * @package App\Notifications\Organizations\Products
 */
class ProductReservedNotification extends BaseProductsNotification
{
    protected static $key = 'notifications_products.reserved';
    protected static $permissions = 'manage_products';
    protected static $sendMail = true;

    public function toMail(Identity $identity): void
    {
        $mailable = new ProductBoughtProviderMail(array_merge($this->eventLog->data, [
            'provider_dashboard_link' => Implementation::general()->urlProviderDashboard(),
        ]), Implementation::general()->emailFrom());

        $this->sendMailNotification($identity->primary_email->email, $mailable);
    }
}