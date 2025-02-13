<?php

namespace App\Events\Vouchers;

use App\Models\PhysicalCardRequest;
use App\Models\Voucher;

class VoucherPhysicalCardRequestedEvent extends BaseVoucherEvent
{
    protected Voucher $voucher;
    protected PhysicalCardRequest $cardRequest;
    protected bool $shouldNotifyRequester;

    /**
     * @param Voucher $voucher
     * @param PhysicalCardRequest $cardRequest
     * @param bool $shouldNotifyRequester
     */
    public function __construct(
        Voucher $voucher,
        PhysicalCardRequest $cardRequest,
        bool $shouldNotifyRequester = false
    ) {
        parent::__construct($voucher);

        $this->voucher = $voucher;
        $this->cardRequest = $cardRequest;
        $this->shouldNotifyRequester = $shouldNotifyRequester;
    }

    /**
     * @return PhysicalCardRequest
     */
    public function getCardRequest(): PhysicalCardRequest
    {
        return $this->cardRequest;
    }

    /**
     * @return bool
     */
    public function shouldNotifyRequester(): bool
    {
        return $this->shouldNotifyRequester;
    }
}
