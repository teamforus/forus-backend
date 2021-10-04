<?php

namespace App\Events\PhysicalCardRequests;

use App\Models\PhysicalCardRequest;
use App\Models\Voucher;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class PhysicalCardRequestsCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $physicalCardRequest;
    private $voucher;

    /**
     * Create a new event instance.
     *
     * @param PhysicalCardRequest $physicalCardRequest
     */
    public function __construct(PhysicalCardRequest $physicalCardRequest)
    {
        $this->physicalCardRequest = $physicalCardRequest;
    }

    /**
     * Get the physical card request
     *
     * @return PhysicalCardRequest
     */
    public function getPhysicalCardRequest(): PhysicalCardRequest
    {
        return $this->physicalCardRequest;
    }

    /**
     * Get the fund
     *
     * @return Voucher
     */
    public function getVoucher(): Voucher
    {
        return $this->physicalCardRequest->voucher;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return PrivateChannel
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}