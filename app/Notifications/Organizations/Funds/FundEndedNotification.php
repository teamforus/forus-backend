<?php

namespace App\Notifications\Organizations\Funds;

use App\Models\Permission;

/**
 * Notify sponsor that the fund has ended.
 */
class FundEndedNotification extends BaseFundsNotification
{
    protected static ?string $key = 'notifications_funds.ended';
    protected static string|array $permissions = Permission::VIEW_FUNDS;
}
