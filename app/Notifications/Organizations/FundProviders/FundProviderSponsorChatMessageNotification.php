<?php

namespace App\Notifications\Organizations\FundProviders;

use App\Models\Notification;
use App\Models\Permission;
use App\Services\EventLogService\Models\EventLog;

/**
 * Notify the provider that a new chat message from the sponsor was received.
 */
class FundProviderSponsorChatMessageNotification extends BaseFundProvidersNotification
{
    protected static ?string $key = 'notifications_fund_providers.sponsor_message';
    protected static string|array $permissions = Permission::MANAGE_PROVIDER_FUNDS;

    /**
     * @param Notification $notification
     * @param EventLog $event
     * @return array|null
     */
    public static function getDashboardRoute(Notification $notification, EventLog $event): ?array
    {
        return static::makeDashboardRoute('products-show', [
            'organizationId' => $notification->organization_id,
            'id' => $event->data['product_id'] ?? null,
        ], [
            'chat_fund_id' => $event->data['fund_id'] ?? null,
        ]);
    }
}
