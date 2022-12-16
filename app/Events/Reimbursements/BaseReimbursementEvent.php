<?php

namespace App\Events\Reimbursements;

use App\Models\Employee;
use App\Models\Voucher;
use App\Models\Reimbursement;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

abstract class BaseReimbursementEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected Reimbursement $reimbursement;
    protected ?Employee $employee;
    protected ?Employee $supervisorEmployee;

    /**
     * Create a new event instance.
     *
     * @param Reimbursement $reimbursement
     * @param Employee|null $employee
     * @param Employee|null $supervisorEmployee
     */
    public function __construct(
        Reimbursement $reimbursement,
        Employee $employee = null,
        ?Employee $supervisorEmployee = null
    ) {
        $this->reimbursement = $reimbursement;
        $this->employee = $employee;
        $this->supervisorEmployee = $supervisorEmployee;
    }

    /**
     * Get the fund request
     *
     * @return Reimbursement
     */
    public function getReimbursement(): Reimbursement
    {
        return $this->reimbursement;
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
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('channel-name');
    }
}
