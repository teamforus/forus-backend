<?php

namespace App\Events\BankConnections;

use App\Models\BankConnection;
use App\Models\Employee;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

abstract class BaseBankConnectionEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected BankConnection $bankConnection;
    protected ?Employee $employee;
    protected array $data;

    /**
     * Create a new event instance.
     *
     * @param BankConnection $bankConnection
     * @param Employee|null $employee
     * @param array $data
     */
    public function __construct(
        BankConnection $bankConnection,
        ?Employee $employee = null,
        array $data = []
    ) {
        $this->bankConnection = $bankConnection;
        $this->employee = $employee;
        $this->data = $data;
    }

    /**
     * @return BankConnection
     */
    public function getBankConnection(): BankConnection
    {
        return $this->bankConnection;
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
