<?php

namespace App\Events\Vouchers;

use App\Models\Employee;
use App\Models\Voucher;

/**
 * Class VoucherCreated
 * @package App\Events\Vouchers
 */
class VoucherDeactivated extends BaseVoucherEvent
{
    protected $note;
    protected $employee;
    protected $notifyByEmail;

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
