<?php

namespace App\Notifications\Organizations\BankConnections;

class BankConnectionMonetaryAccountChangedNotification extends BaseBankConnectionsNotification
{
    protected static ?string $key = 'notifications_bank_connections.monetary_account_changed';
    protected static string|array $permissions = 'manage_bank_connections';
}
