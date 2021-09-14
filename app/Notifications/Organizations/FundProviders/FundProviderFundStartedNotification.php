<?php

namespace App\Notifications\Organizations\FundProviders;

/**
 * Class FundProviderFundStartedNotification
 * @package App\Notifications\Organizations\FundProviders
 */
class FundProviderFundStartedNotification extends BaseFundProvidersNotification
{
    protected static $key = 'notifications_fund_providers.fund_started';
    protected static $permissions = 'manage_provider_funds';
    protected static $visible = true;
}
