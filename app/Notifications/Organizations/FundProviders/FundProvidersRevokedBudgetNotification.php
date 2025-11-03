<?php

namespace App\Notifications\Organizations\FundProviders;

use App\Models\Permission;

/**
 * Notify the fund provider that they can no longer scan budget vouchers.
 */
class FundProvidersRevokedBudgetNotification extends BaseFundProvidersNotification
{
    protected static ?string $key = 'notifications_fund_providers.revoked_budget';
    protected static string|array $permissions = Permission::MANAGE_PROVIDER_FUNDS;
}
