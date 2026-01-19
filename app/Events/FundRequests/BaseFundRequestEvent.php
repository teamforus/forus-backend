<?php

namespace App\Events\FundRequests;

use App\Models\Employee;
use App\Models\Fund;
use App\Models\FundRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class BaseFundRequestEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param FundRequest $fundRequest
     * @param Employee|null $employee
     * @param Employee|null $supervisorEmployee
     */
    public function __construct(
        protected FundRequest $fundRequest,
        protected ?Employee $employee = null,
        protected ?Employee $supervisorEmployee = null,
    ) {
    }

    /**
     * Get the fund request.
     *
     * @return FundRequest
     */
    public function getFundRequest(): FundRequest
    {
        return $this->fundRequest;
    }

    /**
     * Get the fund request.
     *
     * @return Fund
     */
    public function getFund(): Fund
    {
        return $this->fundRequest->fund;
    }

    /**
     * @return Employee|null
     */
    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }

    /**
     * @return Employee|null
     */
    public function getSupervisorEmployee(): ?Employee
    {
        return $this->supervisorEmployee;
    }
}
