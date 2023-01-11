<?php

namespace App\Events\ProductReservations;

use App\Models\ProductReservation;

class ProductReservationCanceled extends BaseProductReservationEvent
{
    /**
     * @var bool
     */
    protected bool $byClient = false;

    /**
     * @param ProductReservation $productReservation
     * @param bool $byClient
     */
    public function __construct(ProductReservation $productReservation, bool $byClient = false)
    {
        parent::__construct($productReservation);
        $this->byClient = $byClient;
    }

    /**
     * @return bool
     */
    public function isByClient(): bool
    {
        return $this->byClient;
    }
}
