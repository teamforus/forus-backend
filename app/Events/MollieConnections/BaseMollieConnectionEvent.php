<?php

namespace App\Events\MollieConnections;

use App\Models\Employee;
use App\Services\MollieService\Models\MollieConnection;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

abstract class BaseMollieConnectionEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected MollieConnection $mollieConnection;
    protected ?Employee $employee;
    protected array $data;

    /**
     * Create a new event instance.
     *
     * @param MollieConnection $mollieConnection
     * @param Employee|null $employee
     * @param array $data
     */
    public function __construct(
        MollieConnection $mollieConnection,
        ?Employee $employee = null,
        array $data = []
    ) {
        $this->mollieConnection = $mollieConnection;
        $this->employee = $employee;
        $this->data = $data;
    }

    /**
     * @return MollieConnection
     */
    public function getMollieConnection(): MollieConnection
    {
        return $this->mollieConnection;
    }

    /**
     * @return Employee|null
     */
    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
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
