<?php

namespace App\Notifications\Organizations\FundProviders;

use App\Models\Permission;

/**
 * Notify provider that a fund they supply will end soon.
 */
class FundProviderFundExpiringNotification extends BaseFundProvidersNotification
{
    protected static ?string $key = 'notifications_fund_providers.fund_expiring';
    protected static string|array $permissions = Permission::MANAGE_PROVIDER_FUNDS;
}
