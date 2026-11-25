<?php

namespace App\Events\Funds;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Product;

class FundProductAddedEvent extends BaseFundEvent
{
    protected Product $product;
    protected FundProvider $fundProvider;

    /**
     * Create a new event instance.
     *
     * @param Fund $fund
     * @param FundProvider $fundProvider
     * @param Product $product
     */
    public function __construct(Fund $fund, FundProvider $fundProvider, Product $product)
    {
        parent::__construct($fund);
        $this->product = $product;
        $this->fundProvider = $fundProvider;
    }

    /**
     * @return Product
     */
    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * @return FundProvider
     */
    public function getFundProvider(): FundProvider
    {
        return $this->fundProvider;
    }
}
