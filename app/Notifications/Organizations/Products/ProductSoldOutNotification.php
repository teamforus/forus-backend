<?php

namespace App\Notifications\Organizations\Products;

use App\Mail\Vouchers\ProductSoldOutMail;
use App\Models\Implementation;
use App\Models\Identity;

/**
 * The product was sold out
 */
class ProductSoldOutNotification extends BaseProductsNotification
{
    protected static ?string $key = 'notifications_products.sold_out';
    protected static string|array $permissions = 'manage_products';

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
