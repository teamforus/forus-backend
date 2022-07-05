<?php

namespace App\Notifications\Identities\ProductReservation;

/**
 * The product reservation was canceled
 */
class IdentityProductReservationCanceledNotification extends BaseProductReservationNotification
{
    protected static ?string $key = 'notifications_identities.product_reservation_canceled';
}
