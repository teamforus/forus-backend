<?php

namespace App\Notifications\Organizations\Products;

use App\Mail\Vouchers\ProductBoughtProviderBySponsorMail;
use App\Models\Identity;
use App\Models\Implementation;

/**
 * The product was reserved by the sponsor.
 */
class ProductReservedBySponsorNotification extends BaseProductsNotification
{
    protected static ?string $key = 'notifications_products.reserved_by_sponsor';
    protected static string|array $permissions = 'manage_products';

    /**
     * @param Identity $identity
     * @return void
     */
    public function toMail(Identity $identity): void
    {
        $mailable = new ProductBoughtProviderBySponsorMail([
            ...$this->eventLog->data,
            'provider_dashboard_link' => Implementation::general()->urlProviderDashboard(),
        ], Implementation::general()->emailFrom());

        $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
    }
}
