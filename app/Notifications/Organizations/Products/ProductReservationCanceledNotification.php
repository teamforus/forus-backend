<?php

namespace App\Notifications\Organizations\Products;

use App\Notifications\Organizations\Products\BaseProductsNotification;

/**
 * The product reservation was canceled
 */
class ProductReservationCanceledNotification extends BaseProductsNotification
{
    protected static ?string $key = 'notifications_identities.product_reservation_canceled';
}
