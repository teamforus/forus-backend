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
    protected Voucher $voucher;
    protected PhysicalCardRequest $cardRequest;
    protected bool $shouldNotifyRequester;

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
