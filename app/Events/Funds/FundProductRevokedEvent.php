<?php

namespace App\Events\Funds;

use App\Models\Fund;
use App\Models\Product;

class FundProductRevokedEvent extends BaseFundEvent
{
    protected $product;

    /**
     * Create a new event instance.
     *
     * @param Fund $fund
     * @param Product $product
     */
    public function __construct(Fund $fund, Product $product)
    {
        parent::__construct($fund);
        $this->product = $product;
    }

    /**
     * @return Product
     */
    public function getProduct(): Product
    {
        return $this->product;
    }
}
