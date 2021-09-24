<?php

namespace App\Notifications\Identities\ProductReservation;

/**
 * A new product reservation was created
 */
class IdentityProductReservationCreatedNotification extends BaseProductReservationNotification
{
    protected static $key = 'notifications_identities.product_reservation_created';
}
