<?php

namespace App\Notifications\Organizations\Products;

class ProductApprovedNotification extends BaseProductsNotification
{
    protected static ?string $key = 'notifications_products.approved';
    protected static string|array $permissions = 'manage_products';
}
