<?php

namespace App\Notifications\Organizations\Funds;

/**
 * Notify sponsor that the fund balance was successfully supplied
 */
class BalanceSuppliedNotification extends BaseFundsNotification
{
    protected static ?string $key = 'notifications_funds.balance_supplied';
    protected static string|array $permissions = 'view_finances';
}
