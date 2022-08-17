<?php

namespace App\Notifications\Organizations\FundProviders;

/**
 * Notify fund provider that they got approved to sell all of their products on the webshop
 */
class FundProvidersApprovedProductsNotification extends BaseFundProvidersNotification
{
    protected static ?string $key = 'notifications_fund_providers.approved_products';
    protected static string|array $permissions = 'manage_provider_funds';
}
