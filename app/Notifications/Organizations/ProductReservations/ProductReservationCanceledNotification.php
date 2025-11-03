<?php

namespace App\Notifications\Organizations\ProductReservations;

use App\Models\Permission;

/**
 * The product reservation was canceled by client.
 */
class ProductReservationCanceledNotification extends BaseProductReservationsNotification
{
    protected static ?string $key = 'notifications_products.reservation_canceled';
    protected static string|array $permissions = Permission::MANAGE_PRODUCTS;
}
