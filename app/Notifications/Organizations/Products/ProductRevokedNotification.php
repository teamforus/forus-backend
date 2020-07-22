<?php

namespace App\Notifications\Organizations\Products;

/**
 * Class ProductRevokedNotification
 * @package App\Notifications\Organizations\Products
 */
class ProductRevokedNotification extends BaseProductsNotification {
    protected $key = 'notifications_products.reserved';
    protected static $permissions = [
        'manage_products'
    ];
}
