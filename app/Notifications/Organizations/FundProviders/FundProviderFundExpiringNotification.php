<?php

namespace App\Notifications\Organizations\FundProviders;

/**
 * Class FundProviderFundExpiringNotification
 * @package App\Notifications\Organizations\FundProviders
 */
class FundProviderFundExpiringNotification extends BaseFundProvidersNotification
{
    protected static $key = 'notifications_fund_providers.fund_expiring';
    protected static $permissions = 'manage_provider_funds';
    protected static $visible = true;
}
