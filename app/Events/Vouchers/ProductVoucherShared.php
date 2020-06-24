<?php

namespace App\Events\Vouchers;

use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class ProductVoucherShared
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $voucher;
    private $message;

    /**
     * Create a new event instance.
     *
     * @param Voucher $voucher
     * @param $message
     */
    public function __construct(
        Voucher $voucher,
        $message
    ) {
        $this->voucher = $voucher;
        $this->message = $message;
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
     * Get the voucher product
     *
     * @return Product
     */
    public function getProduct()
    {
        return $this->voucher->product;
    }

    /**
     * Get the share message product
     *
     * @return Product
     */
    public function getMessage()
    {
        return $this->message;
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
