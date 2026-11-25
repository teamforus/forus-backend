<?php

namespace App\Notifications\Organizations\BankConnections;

use App\Models\Notification;
use App\Models\Permission;
use App\Services\EventLogService\Models\EventLog;

class BankConnectionDisabledInvalidNotification extends BaseBankConnectionsNotification
{
    protected static ?string $key = 'notifications_bank_connections.disabled_invalid';
    protected static string|array $permissions = Permission::MANAGE_BANK_CONNECTIONS;

    /**
     * @param Notification $notification
     * @param EventLog $event
     * @return array|null
     */
    public static function getDashboardRoute(Notification $notification, EventLog $event): ?array
    {
        return static::makeDashboardRoute('bank-connections', [
            'organizationId' => $notification->organization_id,
        ]);
    }
}
