<?php

namespace App\Notifications\Organizations\PhysicalCardRequest;

use App\Models\Notification;
use App\Models\Permission;
use App\Services\EventLogService\Models\EventLog;

/**
 * Class PhysicalCardRequestCreatedSponsorNotification.
 */
class PhysicalCardRequestCreatedSponsorNotification extends BasePhysicalCardRequestNotification
{
    protected static ?string $key = 'notifications_physical_card_requests.physical_card_request_created';
    protected static string|array $permissions = Permission::MANAGE_VOUCHERS;

    /**
     * @param Notification $notification
     * @param EventLog $event
     * @return array|null
     */
    public static function getDashboardRoute(Notification $notification, EventLog $event): ?array
    {
        return static::makeDashboardRoute('vouchers-show', [
            'organizationId' => $notification->organization_id,
            'id' => $event->data['voucher_id'] ?? null,
        ]);
    }
}
