<?php

namespace App\Services\EventLogService\Events;

use App\Services\EventLogService\Models\EventLog;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EventLogCreated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param EventLog $eventLog
     */
    public function __construct(protected EventLog $eventLog)
    {
    }

    /**
     * @return EventLog
     */
    public function getEventLog(): EventLog
    {
        return $this->eventLog;
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
