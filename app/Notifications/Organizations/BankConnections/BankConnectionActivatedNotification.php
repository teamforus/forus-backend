<?php

namespace App\Notifications\Organizations\BankConnections;

class BankConnectionActivatedNotification extends BaseBankConnectionsNotification
{
    protected static ?string $key = 'notifications_bank_connections.activated';
    protected static string|array $permissions = 'manage_bank_connections';
}
