<?php

namespace App\Events\FundRequests;

use App\Models\Employee;
use App\Models\Fund;
use App\Models\FundRequest;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

abstract class BaseFundRequestEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected FundRequest $fundRequest;
    protected ?Employee $employee;
    protected ?Employee $supervisorEmployee;

    /**
     * Create a new event instance.
     *
     * @param FundRequest $fundRequest
     * @param Employee|null $employee
     * @param Employee|null $supervisorEmployee
     */
    public function __construct(
        FundRequest $fundRequest,
        Employee $employee = null,
        ?Employee $supervisorEmployee = null
    ) {
        $this->fundRequest = $fundRequest;
        $this->employee = $employee;
        $this->supervisorEmployee = $supervisorEmployee;
    }

    /**
     * Get the fund request
     *
     * @return FundRequest
     */
    public function getFundRequest(): FundRequest
    {
        return $this->fundRequest;
    }

    /**
     * Get the fund request
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
