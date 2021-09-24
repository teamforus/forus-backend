<?php

namespace App\Notifications\Identities\ProductReservation;

/**
 * The product reservation was rejected
 */
class IdentityProductReservationRejectedNotification extends BaseProductReservationNotification
{
    protected static $key = 'notifications_identities.product_reservation_rejected';
}
