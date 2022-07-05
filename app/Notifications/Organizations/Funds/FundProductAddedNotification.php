<?php

namespace App\Notifications\Organizations\Funds;

/**
 * Notify sponsor that a new product was added to the webshop by a provider
 */
class FundProductAddedNotification extends BaseFundsNotification
{
    protected static ?string $key = 'notifications_funds.product_added';
    protected static $permissions = 'manage_providers';
}
