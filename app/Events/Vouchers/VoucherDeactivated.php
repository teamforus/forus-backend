<?php

namespace App\Events\Vouchers;

use App\Models\Employee;
use App\Models\Voucher;

class VoucherDeactivated extends BaseVoucherEvent
{
    protected string $note;
    protected ?Employee $employee;
    protected bool $notifyByEmail;

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
        parent::__construct($voucher);

        $this->note = $note;
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
}
