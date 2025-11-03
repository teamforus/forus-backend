<?php

namespace App\Notifications\Organizations\Products;

use App\Models\Permission;

/**
 * The product expired.
 */
class ProductExpiredNotification extends BaseProductsNotification
{
    protected static ?string $key = 'notifications_products.expired';
    protected static string|array $permissions = Permission::MANAGE_PRODUCTS;
}
