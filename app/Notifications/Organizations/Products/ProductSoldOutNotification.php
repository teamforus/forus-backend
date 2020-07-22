<?php

namespace App\Notifications\Organizations\Products;

/**
 * Class ProductSoldOutNotification
 * @package App\Notifications\Organizations\Products
 */
class ProductSoldOutNotification extends BaseProductsNotification {
    protected $key = 'notifications_products.sold_out';
    protected static $permissions = [
        'manage_products'
    ];
}
