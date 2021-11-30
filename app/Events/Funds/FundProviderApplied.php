<?php

namespace App\Events\Funds;

use App\Models\Fund;
use App\Models\FundProvider;

class FundProviderApplied extends BaseFundEvent
{
    private $fundProvider;

    /**
     * Create a new event instance.
     *
     * @param Fund $fund
     * @param FundProvider $fundProvider
     */
    public function __construct(Fund $fund, FundProvider $fundProvider)
    {
        parent::__construct($fund);
        $this->fundProvider = $fundProvider;
    }

    /**
     * Get the voucher
     *
     * @return FundProvider
     */
    public function getFundProvider(): FundProvider
    {
        return $this->fundProvider;
    }
}
