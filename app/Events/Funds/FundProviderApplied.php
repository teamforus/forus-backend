<?php

namespace App\Events\Funds;

use App\Models\FundProvider;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class FundProviderApplied
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $fund;
    private $fundProvider;

    /**
     * Create a new event instance.
     *
     * @param FundProvider $fundProvider
     */
    public function __construct(FundProvider $fundProvider)
    {
        $this->fundProvider = $fundProvider;
    }

    /**
     * Get the voucher
     *
     * @return FundProvider
     */
    public function getFundProvider()
    {
        return $this->fundProvider;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
