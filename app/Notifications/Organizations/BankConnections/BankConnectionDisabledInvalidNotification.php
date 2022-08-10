<?php

namespace App\Notifications\Organizations\BankConnections;

class BankConnectionDisabledInvalidNotification extends BaseBankConnectionsNotification
{
    protected static ?string $key = 'notifications_bank_connections.disabled_invalid';
    protected static string|array $permissions = 'manage_bank_connections';
}
