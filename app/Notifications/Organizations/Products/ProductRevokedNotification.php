<?php

namespace App\Notifications\Organizations\Products;

/**
 * Class ProductRevokedNotification
 * @package App\Notifications\Organizations\Products
 */
class ProductRevokedNotification extends BaseProductsNotification
{
    protected static $key = 'notifications_products.revoked';
    protected static $permissions = 'manage_products';
}
