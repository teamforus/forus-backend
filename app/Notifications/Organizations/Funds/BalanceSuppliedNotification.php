<?php

namespace App\Notifications\Organizations\Funds;

use App\Models\Permission;

/**
 * Notify sponsor that the fund balance was successfully supplied.
 */
class BalanceSuppliedNotification extends BaseFundsNotification
{
    protected static ?string $key = 'notifications_funds.balance_supplied';
    protected static string|array $permissions = Permission::VIEW_FINANCES;
}
