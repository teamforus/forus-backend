<?php

namespace App\Notifications\Identities\ProductReservation;

/**
 * Class IdentityProductReservationAcceptedNotification
 * @package App\Notifications\Identities\ProductReservation
 */
class IdentityProductReservationRejectedNotification extends BaseProductReservationNotification
{
    protected $key = 'notifications_identities.product_reservation_rejected';
}
