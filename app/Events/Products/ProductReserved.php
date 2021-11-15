<?php

namespace App\Events\Products;

use App\Models\Product;
use App\Models\Voucher;

/**
 * Class ProductReserved
 * @package App\Events\Products
 */
class ProductReserved extends BaseProductEvent
{
    protected $voucher;

    /**
     * Create a new event instance.
     *
     * ProductReserved constructor.
     * @param Product $product
     * @param Voucher $voucher
     */
    public function __construct(Product $product, Voucher $voucher)
    {
        parent::__construct($product);
        $this->voucher = $voucher;
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
}
