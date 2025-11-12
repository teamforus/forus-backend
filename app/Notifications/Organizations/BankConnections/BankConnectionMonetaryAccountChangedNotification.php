<?php

namespace App\Notifications\Organizations\BankConnections;

use App\Models\Permission;

class BankConnectionMonetaryAccountChangedNotification extends BaseBankConnectionsNotification
{
    protected static ?string $key = 'notifications_bank_connections.monetary_account_changed';
    protected static string|array $permissions = Permission::MANAGE_BANK_CONNECTIONS;
}
