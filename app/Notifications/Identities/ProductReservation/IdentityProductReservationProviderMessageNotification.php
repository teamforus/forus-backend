<?php

namespace App\Notifications\Identities\ProductReservation;

use App\Mail\ProductReservations\ProductReservationProviderMessageMail;
use App\Models\Identity;
use App\Models\ProductReservation;
use Illuminate\Support\Arr;

class IdentityProductReservationProviderMessageNotification extends BaseProductReservationNotification
{
    protected static ?string $key = 'notifications_identities.product_reservation_message';

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var ProductReservation $reservation */
        $reservation = $this->eventLog->loggable;
        $implementation = $reservation->voucher->fund->fund_config->implementation;

        $message = Arr::get($this->eventLog->data, 'product_reservation_provider_message');

        $mailable = new ProductReservationProviderMessageMail([
            ...$this->eventLog->data,
            'provider_message' => $message,
            'webshop_link' => $implementation->urlWebshop("/reservations/$reservation->id"),
        ], $reservation->voucher->fund->getEmailFrom());

        $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
    }
}
