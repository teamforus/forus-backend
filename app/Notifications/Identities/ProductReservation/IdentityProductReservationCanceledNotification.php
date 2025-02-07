<?php

namespace App\Notifications\Identities\ProductReservation;

use App\Mail\ProductReservations\ProductReservationCanceledMail;
use App\Models\Identity;
use App\Models\ProductReservation;

/**
 * The product reservation was canceled
 */
class IdentityProductReservationCanceledNotification extends BaseProductReservationNotification
{
    protected static ?string $key = 'notifications_identities.product_reservation_canceled';

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var ProductReservation $reservation */
        $reservation = $this->eventLog->loggable;
        $implementation = $reservation->voucher->fund->fund_config->implementation;
        $refundedExtra = $reservation->extra_payment && $reservation->extra_payment->isFullyRefunded();
        $transKey = 'mails.reservations.extra_payment';

        $mailable = new ProductReservationCanceledMail([
            ...$this->eventLog->data,
            'webshop_link' => $implementation->urlWebshop("/reservations/$reservation->id"),
            'refunded_body' => $refundedExtra ? trans("$transKey.refunded_body") : '',
        ], $reservation->voucher->fund->getEmailFrom());

        $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
    }
}
