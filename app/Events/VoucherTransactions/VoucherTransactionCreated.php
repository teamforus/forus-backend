<?php

namespace App\Events\VoucherTransactions;

use App\Models\VoucherTransaction;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class VoucherTransactionCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $voucherTransaction;

    /**
     * Create a new event instance.
     *
     * @param  VoucherTransaction $voucherTransaction
     * @return void
     */
    public function __construct(
        VoucherTransaction $voucherTransaction
    ) {
        $this->voucherTransaction = $voucherTransaction;
    }

    /**
     * Get the voucher transaction
     *
     * @return VoucherTransaction
     */
    public function getVoucherTransaction()
    {
        return $this->voucherTransaction;
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
