<?php

namespace App\Events\Products;

use App\Models\Product;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

/**
 * Class ProductCreated
 * @package App\Events\Products
 */
abstract class BaseProductEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected Product $product;

    /**
     * Create a new event instance.
     *
     * @param Product $product
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
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
}
