<?php

namespace App\Events\MollieConnections;

use App\Models\Employee;
use App\Services\MollieService\Models\MollieConnection;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

abstract class BaseMollieConnectionEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param MollieConnection $mollieConnection
     * @param Employee|null $employee
     * @param array $data
     */
    public function __construct(
        protected MollieConnection $mollieConnection,
        protected ?Employee $employee = null,
        protected array $data = [],
    ) {}

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
}
