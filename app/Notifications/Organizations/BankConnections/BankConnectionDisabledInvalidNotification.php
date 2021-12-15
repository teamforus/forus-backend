<?php

namespace App\Notifications\Organizations\BankConnections;

class BankConnectionDisabledInvalidNotification extends BaseBankConnectionsNotification
{
    protected static $key = 'notifications_bank_connections.disabled_invalid';
    protected static $permissions = 'manage_bank_connections';
}