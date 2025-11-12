<?php

namespace App\Notifications\Organizations\Funds;

use App\Models\Permission;

/**
 * Notify sponsor that the fund has is expiring.
 */
class FundExpiringNotification extends BaseFundsNotification
{
    protected static ?string $key = 'notifications_funds.expiring';
    protected static string|array $permissions = Permission::VIEW_FUNDS;
}
