<?php

namespace App\Events\ReservationExtraPayments;

use App\Models\Employee;
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
    public function __construct(
        protected ReservationExtraPayment $extraPayment,
        protected ?Employee $employee = null,
        protected array $data = [],
    ) {}

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

    /**
     * @return Employee|null
     */
    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
}
