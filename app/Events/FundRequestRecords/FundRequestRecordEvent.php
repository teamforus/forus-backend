<?php

namespace App\Events\FundRequestRecords;

use App\Models\FundRequestRecord;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class FundRequestRecordEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $fundRequestRecord;

    /**
     * Create a new event instance.
     *
     * @param FundRequestRecord $fundRequestRecord
     */
    public function __construct(FundRequestRecord $fundRequestRecord)
    {
        $this->fundRequestRecord = $fundRequestRecord;
    }

    /**
     * Get the fund request
     *
     * @return FundRequestRecord
     */
    public function getFundRequestRecord()
    {
        return $this->fundRequestRecord;
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
