<?php

namespace App\Notifications\Organizations\FundProviders;

/**
 * Notify provider about a fund going active
 */
class FundProviderFundStartedNotification extends BaseFundProvidersNotification
{
    protected static ?string $key = 'notifications_fund_providers.fund_started';
    protected static string|array $permissions = 'manage_provider_funds';
}
