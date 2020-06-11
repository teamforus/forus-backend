<?php

namespace App\Events\Employees;

use App\Models\Employee;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class EmployeeUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $employee;
    protected $previous_roles;

    /**
     * Create a new event instance.
     *
     * EmployeeUpdated constructor.
     * @param Employee $employee
     * @param array $previous_roles
     */
    public function __construct(
        Employee $employee,
        array $previous_roles
    ) {
        $this->employee = $employee;
        $this->previous_roles = $previous_roles;
    }

    /**
     * Get target user
     *
     * @return Employee
     */
    public function getEmployee()
    {
        return $this->employee;
    }

    /**
     * Get employee roles before update
     *
     * @return array
     */
    public function getPreviousRoles()
    {
        return $this->previous_roles;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
