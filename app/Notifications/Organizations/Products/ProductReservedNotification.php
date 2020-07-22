<?php

namespace App\Notifications\Organizations\Products;

/**
 * Class ProductReservedNotification
 * @package App\Notifications\Organizations\Products
 */
class ProductReservedNotification extends BaseProductsNotification {
    protected $key = 'notifications_products.reserved';
    protected static $permissions = [
        'manage_products'
    ];
}
