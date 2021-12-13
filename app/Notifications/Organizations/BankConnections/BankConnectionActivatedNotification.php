<?php

namespace App\Notifications\Organizations\BankConnections;

class BankConnectionActivatedNotification extends BaseBankConnectionsNotification
{
    protected static $key = 'notifications_bank_connections.activated';
    protected static $permissions = 'manage_bank_connections';
}
