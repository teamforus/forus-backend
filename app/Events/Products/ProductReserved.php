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
     * Get the product
     *
     * @return Voucher
     */
    public function getVoucher(): Voucher
    {
        return $this->voucher;
    }
}
