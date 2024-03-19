<?php

namespace App\Events\Funds;

use App\Models\Fund;
use App\Models\Product;

class FundProductApprovedEvent extends BaseFundEvent
{
    protected $product;

    /**
     * @return Product
     */
    public function getProduct(): Product
    {
        return $this->product;
    }
}
