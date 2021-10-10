<?php

namespace App\Notifications\Organizations\FundProviders;

/**
 * Notify provider about a fund going active
 */
class FundProviderFundStartedNotification extends BaseFundProvidersNotification
{
    protected static $key = 'notifications_fund_providers.fund_started';
    protected static $permissions = 'manage_provider_funds';
}
