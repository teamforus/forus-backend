<?php

namespace App\Notifications\Organizations\ProductReservations;

/**
 * The product reservation was canceled by client
 */
class ProductReservationCanceledNotification extends BaseProductReservationsNotification
{
    protected static ?string $key = 'notifications_products.reservation_canceled';
    protected static string|array $permissions = 'manage_products';
}
