<?php

namespace App\Events\MollieConnections;

use App\Models\Employee;
use App\Services\MollieService\Models\MollieConnection;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class BaseMollieConnectionEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

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
    ) {
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
}
