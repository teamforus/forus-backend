<?php

namespace App\Notifications\Organizations\BankConnections;

class BankConnectionMonetaryAccountChangedNotification extends BaseBankConnectionsNotification
{
    protected static $key = 'notifications_bank_connections.monetary_account_changed';
    protected static $permissions = 'manage_bank_connections';
}
