<?php

namespace App\Events\FundRequestClarifications;

use App\Models\FundRequestClarification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class FundRequestClarificationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $fundRequestClarification;

    /**
     * Create a new event instance.
     *
     * @param FundRequestClarification $fundRequestClarification
     */
    public function __construct(
        FundRequestClarification $fundRequestClarification
    ) {
        $this->fundRequestClarification = $fundRequestClarification;
    }

    /**
     * Get the fund request
     *
     * @return FundRequestClarification
     */
    public function getFundRequestClarification()
    {
        return $this->fundRequestClarification;
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
