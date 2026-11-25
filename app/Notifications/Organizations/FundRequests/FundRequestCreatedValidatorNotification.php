<?php

namespace App\Notifications\Organizations\FundRequests;

use App\Models\Notification;
use App\Models\Permission;
use App\Services\EventLogService\Models\EventLog;

/**
 * Notify sponsor/validator about new fund request.
 */
class FundRequestCreatedValidatorNotification extends BaseFundsRequestsNotification
{
    protected static ?string $key = 'notifications_fund_requests.created_validator_employee';
    protected static string|array $permissions = Permission::VALIDATE_RECORDS;

    /**
     * @param Notification $notification
     * @param EventLog $event
     * @return array|null
     */
    public static function getDashboardRoute(Notification $notification, EventLog $event): ?array
    {
        return static::makeDashboardRoute('fund-request', [
            'organizationId' => $notification->organization_id,
            'id' => $event->data['fund_request_id'] ?? null,
        ]);
    }
}
