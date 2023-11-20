<?php

namespace App\Events\ReservationExtraPayments;

use App\Models\ProductReservation;
use App\Models\ReservationExtraPayment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BaseReservationExtraPaymentEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(protected ReservationExtraPayment $extraPayment) {}

    /**
     * @return ProductReservation
     */
    public function getProductReservation(): ProductReservation
    {
        return $this->extraPayment->product_reservation;
    }

    /**
     * @return ReservationExtraPayment
     */
    public function getReservationExtraPayment(): ReservationExtraPayment
    {
        return $this->extraPayment;
    }
}
