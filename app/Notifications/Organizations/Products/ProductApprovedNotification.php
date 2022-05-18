<?php

namespace App\Notifications\Organizations\Products;

/**
 * Class ProductApprovedNotification
 * @package App\Notifications\Organizations\Products
 */
class ProductApprovedNotification extends BaseProductsNotification
{
    protected static ?string $key = 'notifications_products.approved';
    protected static $permissions = 'manage_products';
}
