<?php

namespace App\Notifications\Organizations\Products;

/**
 * Class ProductExpiredNotification
 * @package App\Notifications\Organizations\Products
 */
class ProductExpiredNotification extends BaseProductsNotification {
    protected $key = 'notifications_products.expired';
    protected static $permissions = [
        'manage_products'
    ];
}
