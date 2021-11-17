<?php

namespace App\Events\Products;

use App\Models\Fund;
use App\Models\Product;

class ProductApproved extends BaseProductEvent
{
    protected $fund;

    /**
     * Create a new event instance.
     *
     * @param Product $product
     * @param Fund $fund
     */
    public function __construct(Product $product, Fund $fund)
    {
        parent::__construct($product);
        $this->fund = $fund;
    }

    /**
     * Get the fund
     *
     * @return Fund
     */
    public function getFund(): Fund
    {
        return $this->fund;
    }
}
