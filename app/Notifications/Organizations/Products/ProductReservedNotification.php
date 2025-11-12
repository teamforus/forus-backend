<?php

namespace App\Notifications\Organizations\Products;

use App\Mail\Vouchers\ProductBoughtProviderMail;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Permission;
use Illuminate\Support\Arr;

/**
 * The product was reserved.
 */
class ProductReservedNotification extends BaseProductsNotification
{
    protected static ?string $key = 'notifications_products.reserved';
    protected static string|array $permissions = Permission::MANAGE_PRODUCTS;

    public function toMail(Identity $identity): void
    {
        $organizationId = $this->getOrganization($this->eventLog->loggable)->id;
        $reservationId = Arr::get($this->eventLog->data, 'product_reservation_id');

        $link = Implementation::general()
            ->urlProviderDashboard("organisaties/$organizationId/reserveringen/$reservationId");

        $mailable = new ProductBoughtProviderMail([
            ...$this->eventLog->data,
            'provider_dashboard_link' => $link,
        ], Implementation::general()->emailFrom());

        $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
    }
}
