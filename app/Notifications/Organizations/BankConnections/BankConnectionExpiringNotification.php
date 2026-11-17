<?php

namespace App\Notifications\Organizations\BankConnections;

use App\Mail\BankConnections\BankConnectionExpiringMail;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Notification;
use App\Models\Permission;
use App\Services\EventLogService\Models\EventLog;

class BankConnectionExpiringNotification extends BaseBankConnectionsNotification
{
    protected static ?string $key = 'notifications_bank_connections.expiring';
    protected static string|array $permissions = Permission::MANAGE_BANK_CONNECTIONS;

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        $implementation = Implementation::general();

        $mailable = new BankConnectionExpiringMail([
            'url_sponsor' => $implementation->urlSponsorDashboard(),
            ...$this->eventLog->data,
        ], $implementation->emailFrom());

        $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
    }

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
