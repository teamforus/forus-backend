<?php

namespace App\Events\PrevalidationRequests;

use App\Models\PrevalidationRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class BasePrevalidationRequestEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    protected PrevalidationRequest $prevalidationRequest;

    /**
     * Create a new event instance.
     *
     * PrevalidationRequestCreated constructor.
     * @param PrevalidationRequest $prevalidationRequest
     */
    public function __construct(PrevalidationRequest $prevalidationRequest)
    {
        $this->prevalidationRequest = $prevalidationRequest;
    }

    /**
     * Get the voucher.
     *
     * @return PrevalidationRequest
     */
    public function getPrevalidationRequest(): PrevalidationRequest
    {
        return $this->prevalidationRequest;
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
