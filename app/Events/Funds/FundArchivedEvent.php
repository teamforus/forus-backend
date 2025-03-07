<?php

namespace App\Events\Funds;

use App\Models\Employee;
use App\Models\Fund;

class FundArchivedEvent extends BaseFundEvent
{
    protected Employee $employee;

    /**
     * @param Fund $fund
     * @param Employee $employee
     */
    public function __construct(Fund $fund, Employee $employee)
    {
        parent::__construct($fund);
        $this->employee = $employee;
    }

    /**
     * @return Employee
     */
    public function getEmployee(): Employee
    {
        return $this->employee;
    }
}
