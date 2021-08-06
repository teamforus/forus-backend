<?php

namespace App\Events\Products;

use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

/**
 * Class ProductReserved
 * @package App\Events\Products
 */
class ProductReserved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $product;
    private $voucher;

    /**
     * Create a new event instance.
     *
     * ProductReserved constructor.
     * @param Product $product
     * @param Voucher $voucher
     */
    public function __construct(Product $product, Voucher $voucher)
    {
        $this->product = $product;
        $this->voucher = $voucher;
    }

    /**
     * Get the product
     *
     * @return Product
     */
    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * Get the product
     *
     * @return Voucher
     */
    public function getVoucher(): Voucher
    {
        return $this->voucher;
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
