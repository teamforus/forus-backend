<?php

namespace App\Events\Funds;

use App\Models\Fund;
use App\Models\FundTopUpTransaction;

class FundBalanceSuppliedEvent extends BaseFundEvent
{
    protected $transaction;

    /**
     * Get the top-up transaction
     *
     * @return FundTopUpTransaction
     */
    public function getTransaction(): FundTopUpTransaction
    {
        return $this->transaction;
    }
}
