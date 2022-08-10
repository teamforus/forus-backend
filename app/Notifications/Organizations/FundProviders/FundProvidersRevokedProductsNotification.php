<?php

namespace App\Notifications\Organizations\FundProviders;

/**
 * Notify the fund provider that their permission to sell any product on webshop was removed
 */
class FundProvidersRevokedProductsNotification extends BaseFundProvidersNotification
{
    protected static ?string $key = 'notifications_fund_providers.revoked_products';
    protected static string|array $permissions = 'manage_provider_funds';
}
