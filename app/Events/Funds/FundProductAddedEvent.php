<?php

namespace App\Events\Funds;

use App\Models\Fund;
use App\Models\Product;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class FundProductAddedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $fund;
    private $product;

    /**
     * Create a new event instance.
     *
     * @param Fund $fund
     * @param Product $product
     */
    public function __construct(Fund $fund, Product $product)
    {
        $this->fund = $fund;
        $this->product = $product;
    }

    /**
     * Get the voucher
     *
     * @return Fund
     */
    public function getFund()
    {
        return $this->fund;
    }

    /**
     * @return Product
     */
    public function getProduct(): Product
    {
        return $this->product;
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
