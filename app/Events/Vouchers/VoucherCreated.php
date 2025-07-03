<?php

namespace App\Events\Vouchers;

use App\Models\Voucher;

class VoucherCreated extends BaseVoucherEvent
{
    protected bool $notifyRequesterAdded;
    protected bool $notifyRequesterReserved;
    protected bool $notifyProviderReserved;
    protected bool $notifyProviderReservedBySponsor;

    /**
     * Create a new event instance.
     *
     * @param Voucher $voucher
     * @param bool $notifyRequesterReserved
     * @param bool $notifyRequesterAdded
     * @param bool $notifyProviderReserved
     * @param bool $notifyProviderReservedBySponsor
     */
    public function __construct(
        Voucher $voucher,
        bool $notifyRequesterReserved = true,
        bool $notifyRequesterAdded = true,
        bool $notifyProviderReserved = true,
        bool $notifyProviderReservedBySponsor = false,
    ) {
        parent::__construct($voucher);

        $this->notifyRequesterAdded = $notifyRequesterAdded;
        $this->notifyRequesterReserved = $notifyRequesterReserved;
        $this->notifyProviderReserved = $notifyProviderReserved;
        $this->notifyProviderReservedBySponsor = $notifyProviderReservedBySponsor;
    }

    /**
     * @return bool
     */
    public function shouldNotifyRequesterReserved(): bool
    {
        return $this->notifyRequesterReserved;
    }

    /**
     * @return bool
     */
    public function shouldNotifyRequesterAdded(): bool
    {
        return $this->notifyRequesterAdded;
    }

    /**
     * @return bool
     */
    public function shouldNotifyProviderReserved(): bool
    {
        return $this->notifyProviderReserved;
    }

    /**
     * @return bool
     */
    public function shouldNotifyProviderReservedBySponsor(): bool
    {
        return $this->notifyProviderReservedBySponsor;
    }
}
