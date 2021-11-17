<?php

namespace App\Events\FundProviders;

use App\Models\FundProvider;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class BaseFundProviderEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $fundProvider;

    /**
     * Create a new event instance.
     *
     * FundProviderApprovedBudget constructor.
     * @param FundProvider $fundProvider
     */
    public function __construct(FundProvider $fundProvider)
    {
        $this->fundProvider = $fundProvider;
    }

    /**
     * @return FundProvider
     */
    public function getFundProvider(): FundProvider
    {
        return $this->fundProvider;
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
