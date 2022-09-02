<?php

namespace App\Notifications\Organizations\Products;

/**
 * The product was revoked from a fund by the sponsor
 */
class ProductRevokedNotification extends BaseProductsNotification
{
    protected static ?string $key = 'notifications_products.revoked';
    protected static string|array $permissions = 'manage_products';
}
