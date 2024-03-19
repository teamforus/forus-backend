<?php

namespace App\Events\Funds;

use App\Models\Fund;
use App\Models\FundProvider;

class FundProviderApplied extends BaseFundEvent
{
    private $fundProvider;

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
