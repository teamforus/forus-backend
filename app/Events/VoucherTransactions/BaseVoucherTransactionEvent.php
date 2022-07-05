<?php

namespace App\Events\VoucherTransactions;

use App\Models\VoucherTransaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

/**
 * Class VoucherTransactionCreated
 * @package App\Events\VoucherTransactions
 */
abstract class BaseVoucherTransactionEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private VoucherTransaction $voucherTransaction;
    private array $logData;

    /**
     * Create a new event instance.
     *
     * @param VoucherTransaction $voucherTransaction
     * @param array $logData
     */
    public function __construct(VoucherTransaction $voucherTransaction, array $logData = [])
    {
        $this->voucherTransaction = $voucherTransaction;
        $this->logData = $logData;
    }

    /**
     * Get the voucher transaction
     *
     * @return VoucherTransaction
     */
    public function getVoucherTransaction(): VoucherTransaction
    {
        return $this->voucherTransaction;
    }

    /**
     * Get raw log data
     *
     * @return array
     */
    public function getLogData(): array
    {
        return $this->logData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('channel-name');
    }
}
