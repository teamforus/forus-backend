<?php

namespace App\Events\Vouchers;

use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

abstract class BaseVoucherEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $voucher;

    /**
     * Create a new event instance.
     *
     * @param  Voucher $voucher
     * @return void
     */
    public function __construct(Voucher $voucher)
    {
        $this->voucher = $voucher;
    }

    /**
     * Get the voucher
     *
     * @return Voucher
     */
    public function getVoucher(): Voucher
    {
        return $this->voucher;
    }

    /**
     * Get the voucher product
     *
     * @return Product|null
     */
    public function getProduct(): ?Product
    {
        return $this->voucher->product;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
