<?php

namespace App\Notifications\Organizations\BankConnections;

use App\Models\Permission;

class BankConnectionActivatedNotification extends BaseBankConnectionsNotification
{
    protected static ?string $key = 'notifications_bank_connections.activated';
    protected static string|array $permissions = Permission::MANAGE_BANK_CONNECTIONS;
}
