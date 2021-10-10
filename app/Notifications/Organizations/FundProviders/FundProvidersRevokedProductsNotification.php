<?php

namespace App\Notifications\Organizations\FundProviders;

/**
 * Notify the fund provider that their permission to sell any product on webshop was removed
 */
class FundProvidersRevokedProductsNotification extends BaseFundProvidersNotification
{
    protected static $key = 'notifications_fund_providers.revoked_products';
    protected static $permissions = 'manage_provider_funds';
}
