<?php

namespace App\Events\Products;

use App\Models\Fund;
use App\Models\Product;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class ProductApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $product;
    private $fund;

    /**
     * Create a new event instance.
     *
     * @param Product $product
     * @param Fund $fund
     */
    public function __construct(Product $product, Fund $fund)
    {
        $this->product = $product;
        $this->fund = $fund;
    }

    /**
     * Get the product
     *
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Get the fund
     *
     * @return Fund
     */
    public function getFund()
    {
        return $this->fund;
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
