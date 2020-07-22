<?php

namespace App\Events\Vouchers;

use App\Models\Voucher;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class VoucherCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $voucher;
    private $notifyRequester;

    /**
     * Create a new event instance.
     *
     * @param Voucher $voucher
     * @param bool $notifyRequester
     */
    public function __construct(
        Voucher $voucher,
        bool $notifyRequester = true
    ) {
        $this->voucher = $voucher;
        $this->notifyRequester = $notifyRequester;
    }

    /**
     * Get the voucher
     *
     * @return Voucher
     */
    public function getVoucher()
    {
        return $this->voucher;
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

    /**
     * @return bool
     */
    public function isNotifyRequester(): bool
    {
        return $this->notifyRequester;
    }
}
