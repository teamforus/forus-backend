<?php

namespace App\Notifications\Organizations\FundProviders;

/**
 * Class FundProvidersRevokedProductsNotification
 * @package App\Notifications\Organizations\FundProviders
 */
class FundProvidersRevokedProductsNotification extends BaseFundProvidersNotification
{
    protected $key = 'notifications_fund_providers.revoked_products';
    protected static $permissions = [
        'manage_provider_funds'
    ];
}
