<?php

namespace App\Events\ProductReservations;

use App\Models\ProductReservation;
use App\Models\ProviderMessage;

class ProductReservationSendProviderMessage extends BaseProductReservationEvent
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        protected ProductReservation $productReservation,
        protected ProviderMessage $providerMessage,
    ) {
        parent::__construct($productReservation);
    }

    /**
     * @return ProviderMessage
     */
    public function getProviderMessage(): ProviderMessage
    {
        return $this->providerMessage;
    }
}
