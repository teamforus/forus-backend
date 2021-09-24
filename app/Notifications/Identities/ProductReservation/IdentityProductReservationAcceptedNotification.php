<?php

namespace App\Notifications\Identities\ProductReservation;

/**
 * The product reservation was accepted
 */
class IdentityProductReservationAcceptedNotification extends BaseProductReservationNotification
{
    protected static $key = 'notifications_identities.product_reservation_accepted';
}
