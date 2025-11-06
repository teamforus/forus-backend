<?php

namespace App\Notifications\Organizations\Funds;

use App\Models\Permission;

class FundCreatedNotification extends BaseFundsNotification
{
    protected static ?string $key = 'notifications_funds.created';
    protected static string|array $permissions = Permission::VIEW_FUNDS;
}
