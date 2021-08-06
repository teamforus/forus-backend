<?php

namespace App\Events\Funds;

use App\Models\Fund;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

abstract class FundBaseEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $fund;

    /**
     * Create a new event instance.
     *
     * @param Fund $fund
     */
    public function __construct(Fund $fund)
    {
        $this->fund = $fund;
    }

    /**
     * Get the voucher
     *
     * @return Fund
     */
    public function getFund(): Fund
    {
        return $this->fund;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
