<?php

namespace App\Notifications\Organizations\Products;

use App\Models\Permission;

class ProductApprovedNotification extends BaseProductsNotification
{
    protected static ?string $key = 'notifications_products.approved';
    protected static string|array $permissions = Permission::MANAGE_PRODUCTS;
}
