<?php

namespace App\Events\DCPOrders;

use App\Models\Employee;
use App\Services\DCPService\Models\DCPOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class BaseDCPOrderEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param DCPOrder $dcpOrder
     * @param Employee|null $employee
     * @param array $data
     */
    public function __construct(
        protected DCPOrder $dcpOrder,
        protected ?Employee $employee = null,
        protected array $data = [],
    ) {
    }

    /**
     * @return DCPOrder
     */
    public function getDCPOrder(): DCPOrder
    {
        return $this->dcpOrder;
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
