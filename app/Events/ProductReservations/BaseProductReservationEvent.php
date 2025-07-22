<?php

namespace App\Events\ProductReservations;

use App\Models\ProductReservation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BaseProductReservationEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        protected ProductReservation $productReservation,
        protected bool $notifyWithNote = false
    ) {

    }

    /**
     * @return ProductReservation
     */
    public function getProductReservation(): ProductReservation
    {
        return $this->productReservation;
    }

    /**
     * @return bool
     */
    public function getNotifyWithNote(): bool
    {
        return $this->notifyWithNote;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('channel-name');
    }
}
