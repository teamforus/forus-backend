<?php

namespace App\Notifications\Organizations\Funds;

use App\Models\Notification;
use App\Models\Permission;
use App\Services\EventLogService\Models\EventLog;

/**
 * Notify sponsor that they have a new product chat message from a provider.
 */
class FundProviderChatMessageNotification extends BaseFundsNotification
{
    protected static ?string $key = 'notifications_funds.provider_message';
    protected static string|array $permissions = Permission::MANAGE_PROVIDERS;

    /**
     * @param Notification $notification
     * @param EventLog $event
     * @return array|null
     */
    public static function getDashboardRoute(Notification $notification, EventLog $event): ?array
    {
        return static::makeDashboardRoute('fund-provider-product', [
            'organizationId' => $notification->organization_id,
            'fundId' => $event->data['fund_id'] ?? null,
            'fundProviderId' => $event->data['fund_provider_id'] ?? null,
            'id' => $event->data['product_id'] ?? null,
        ], [
            'chat' => 1,
        ]);
    }
}
