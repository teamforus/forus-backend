<?php

namespace App\Notifications\Organizations\Funds;

use App\Models\Permission;

/**
 * Notify sponsor that a new product was added to the webshop by a provider.
 */
class FundProductAddedNotification extends BaseFundsNotification
{
    protected static ?string $key = 'notifications_funds.product_added';
    protected static string|array $permissions = Permission::MANAGE_PROVIDERS;
}
