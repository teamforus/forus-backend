<?php

namespace App\Notifications\Organizations\FundProviders;

use App\Models\Permission;

/**
 * Notify provider about a fund going active.
 */
class FundProviderFundStartedNotification extends BaseFundProvidersNotification
{
    protected static ?string $key = 'notifications_fund_providers.fund_started';
    protected static string|array $permissions = Permission::MANAGE_PROVIDER_FUNDS;
}
