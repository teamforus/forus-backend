<?php

namespace App\Notifications\Organizations\Products;

use App\Models\Notification;
use App\Models\Permission;
use App\Services\EventLogService\Models\EventLog;

/**
 * The product expired.
 */
class ProductExpiredNotification extends BaseProductsNotification
{
    protected static ?string $key = 'notifications_products.expired';
    protected static string|array $permissions = Permission::MANAGE_PRODUCTS;

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
        ]);
    }
}
