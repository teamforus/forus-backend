<?php

namespace App\Events\Vouchers;

use App\Models\Employee;
use App\Models\Voucher;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

/**
 * Class VoucherCreated
 * @package App\Events\Vouchers
 */
class VoucherDeactivated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $note;
    private $voucher;
    private $employee;
    private $notifyByEmail;

    /**
     * Create a new event instance.
     *
     * @param Voucher $voucher
     * @param string $note
     * @param Employee|null $employee
     * @param bool $notifyByEmail
     */
    public function __construct(
        Voucher $voucher,
        string $note,
        ?Employee $employee = null,
        bool $notifyByEmail = true
    ) {
        $this->note = $note;
        $this->voucher = $voucher;
        $this->employee = $employee;
        $this->notifyByEmail = $notifyByEmail;
    }

    /**
     * @return string
     */
    public function getNote(): string
    {
        return $this->note;
    }

    /**
     * Get the voucher
     *
     * @return Voucher
     */
    public function getVoucher(): Voucher
    {
        return $this->voucher;
    }

    /**
     * @return Employee|null
     */
    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }

    /**
     * @return bool
     */
    public function shouldNotifyByEmail(): bool
    {
        return $this->notifyByEmail;
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
