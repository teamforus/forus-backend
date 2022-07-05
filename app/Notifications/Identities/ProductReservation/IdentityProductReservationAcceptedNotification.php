<?php

namespace App\Notifications\Identities\ProductReservation;

/**
 * The product reservation was accepted
 */
class IdentityProductReservationAcceptedNotification extends BaseProductReservationNotification
{
    protected static ?string $key = 'notifications_identities.product_reservation_accepted';
}
