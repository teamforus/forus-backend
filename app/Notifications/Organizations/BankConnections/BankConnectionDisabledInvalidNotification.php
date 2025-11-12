<?php

namespace App\Notifications\Organizations\BankConnections;

use App\Models\Permission;

class BankConnectionDisabledInvalidNotification extends BaseBankConnectionsNotification
{
    protected static ?string $key = 'notifications_bank_connections.disabled_invalid';
    protected static string|array $permissions = Permission::MANAGE_BANK_CONNECTIONS;
}
