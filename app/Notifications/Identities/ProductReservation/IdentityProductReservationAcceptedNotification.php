<?php

namespace App\Notifications\Identities\ProductReservation;

use App\Mail\ProductReservations\ProductReservationAcceptedMail;
use App\Models\Identity;
use App\Models\ProductReservation;

/**
 * The product reservation was accepted
 */
class IdentityProductReservationAcceptedNotification extends BaseProductReservationNotification
{
    protected static ?string $key = 'notifications_identities.product_reservation_accepted';

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var ProductReservation $productReservation */
        $productReservation = $this->eventLog->loggable;

        $mailable = new ProductReservationAcceptedMail(array_merge($this->eventLog->data, [
            'webshop_link' => $productReservation->voucher->fund->urlWebshop(),
        ]), $productReservation->voucher->fund->getEmailFrom());

        $this->sendMailNotification($identity->email, $mailable);
    }
}
