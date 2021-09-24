<?php

namespace App\Notifications\Organizations\Products;

/**
 * The product expired
 */
class ProductExpiredNotification extends BaseProductsNotification
{
    protected static $key = 'notifications_products.expired';
    protected static $permissions = 'manage_products';
}
