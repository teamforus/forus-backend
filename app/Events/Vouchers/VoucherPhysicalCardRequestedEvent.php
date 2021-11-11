<?php

namespace App\Events\Vouchers;

use App\Models\PhysicalCardRequest;
use App\Models\Voucher;

/**
 * Class FundCreated
 * @package App\Events\Funds
 */
class VoucherPhysicalCardRequestedEvent extends BaseVoucherEvent
{
    protected $cardRequest;
    protected $voucher;

    /**
     * @param Voucher $voucher
     * @param PhysicalCardRequest $cardRequest
     */
    public function __construct(Voucher $voucher, PhysicalCardRequest $cardRequest)
    {
        parent::__construct($voucher);

        $this->voucher = $voucher;
        $this->cardRequest = $cardRequest;
    }

    /**
     * @return PhysicalCardRequest
     */
    public function getCardRequest(): PhysicalCardRequest
    {
        return $this->cardRequest;
    }
}
