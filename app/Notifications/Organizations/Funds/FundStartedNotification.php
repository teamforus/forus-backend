<?php

namespace App\Notifications\Organizations\Funds;

use App\Models\Permission;

/**
 * Notify sponsor that the fund has started.
 */
class FundStartedNotification extends BaseFundsNotification
{
    protected static ?string $key = 'notifications_funds.started';
    protected static string|array $permissions = Permission::VIEW_FUNDS;
}
