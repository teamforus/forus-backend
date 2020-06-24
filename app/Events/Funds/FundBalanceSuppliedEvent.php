<?php

namespace App\Events\Funds;

use App\Models\Fund;
use App\Models\FundTopUpTransaction;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class FundBalanceSuppliedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $transaction;

    /**
     * Create a new event instance.
     *
     * FundBalanceSuppliedEvent constructor.
     * @param FundTopUpTransaction $transaction
     */
    public function __construct(FundTopUpTransaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Get the voucher
     *
     * @return Fund
     */
    public function getFund()
    {
        return $this->transaction->fund_top_up->fund;
    }

    /**
     * Get the top-up transaction
     *
     * @return FundTopUpTransaction
     */
    public function getTransaction()
    {
        return $this->transaction;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
