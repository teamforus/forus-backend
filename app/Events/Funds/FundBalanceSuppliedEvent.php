<?php

namespace App\Events\Funds;

use App\Models\Fund;
use App\Models\FundTopUpTransaction;

class FundBalanceSuppliedEvent extends BaseFundEvent
{
    protected $transaction;

    /**
     * Create a new event instance.
     *
     * FundBalanceSuppliedEvent constructor.
     * @param Fund $fund
     * @param FundTopUpTransaction $transaction
     */
    public function __construct(Fund $fund, FundTopUpTransaction $transaction)
    {
        parent::__construct($fund);
        $this->transaction = $transaction;
    }

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
