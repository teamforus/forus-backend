<?php

namespace App\Notifications\Organizations\FundProviders;

/**
 * Notify fund provider that they got approved to sell all of their products on the webshop
 */
class FundProvidersApprovedProductsNotification extends BaseFundProvidersNotification
{
    protected static $key = 'notifications_fund_providers.approved_products';
    protected static $permissions = 'manage_provider_funds';
}
