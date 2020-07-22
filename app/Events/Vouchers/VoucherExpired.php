<?php

namespace App\Events\Vouchers;

use App\Models\Voucher;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class VoucherExpired
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $voucher;

    /**
     * Create a new event instance.
     *
     * @param  Voucher $voucher
     * @return void
     */
    public function __construct(
        Voucher $voucher
    ) {
        $this->voucher = $voucher;
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
}
