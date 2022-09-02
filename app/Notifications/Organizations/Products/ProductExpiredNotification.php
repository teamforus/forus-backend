<?php

namespace App\Notifications\Organizations\Products;

/**
 * The product expired
 */
class ProductExpiredNotification extends BaseProductsNotification
{
    protected static ?string $key = 'notifications_products.expired';
    protected static string|array $permissions = 'manage_products';
}
