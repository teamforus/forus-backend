<?php

namespace App\Notifications\Identities\ProductReservation;

use App\Mail\ProductReservations\ProductReservationAcceptedMail;
use App\Models\Identity;
use App\Models\ProductReservation;
use Illuminate\Support\Arr;

/**
 * The product reservation was accepted.
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

        $providerMessage = Arr::get($this->eventLog->data, 'product_reservation_add_note_to_requester_notification', false)
            ? $productReservation->accepted_note
            : null;

        $mailable = new ProductReservationAcceptedMail([
            ...$this->eventLog->data,
            'provider_note' => $providerMessage,
            'note' => $productReservation->product->getReservationNoteValue(),
            'webshop_link' => $productReservation->voucher->fund->urlWebshop(),
        ], $productReservation->voucher->fund->getEmailFrom());

        $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
    }
}
