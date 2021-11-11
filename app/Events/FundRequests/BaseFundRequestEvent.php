<?php

namespace App\Events\FundRequests;

use App\Models\Fund;
use App\Models\FundRequest;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

abstract class BaseFundRequestEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $fundRequest;

    /**
     * Create a new event instance.
     *
     * @param FundRequest $fundRequest
     */
    public function __construct(FundRequest $fundRequest)
    {
        $this->fundRequest = $fundRequest;
    }

    /**
     * Get the fund request
     *
     * @return FundRequest
     */
    public function getFundRequest(): FundRequest
    {
        return $this->fundRequest;
    }

    /**
     * Get the fund request
     *
     * @return Fund
     */
    public function getFund(): Fund
    {
        return $this->fundRequest->fund;
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
