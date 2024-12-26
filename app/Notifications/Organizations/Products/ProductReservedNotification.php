<?php

namespace App\Notifications\Organizations\Products;

use App\Mail\Vouchers\ProductBoughtProviderMail;
use App\Models\Identity;
use App\Models\Implementation;

/**
 * The product was reserved
 */
class ProductReservedNotification extends BaseProductsNotification
{
    protected static ?string $key = 'notifications_products.reserved';
    protected static string|array $permissions = 'manage_products';

    public function toMail(Identity $identity): void
    {
        $mailable = new ProductBoughtProviderMail([
            ...$this->eventLog->data,
            'provider_dashboard_link' => Implementation::general()->urlProviderDashboard(),
        ], Implementation::general()->emailFrom());

        $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
    }
}