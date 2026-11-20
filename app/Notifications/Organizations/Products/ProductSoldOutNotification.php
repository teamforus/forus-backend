<?php

namespace App\Notifications\Organizations\Products;

use App\Mail\Vouchers\ProductSoldOutMail;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Notification;
use App\Models\Permission;
use App\Services\EventLogService\Models\EventLog;

/**
 * The product was sold out.
 */
class ProductSoldOutNotification extends BaseProductsNotification
{
    protected static ?string $key = 'notifications_products.sold_out';
    protected static string|array $permissions = Permission::MANAGE_PRODUCTS;

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        $emailFrom = Implementation::general()->getEmailFrom();

        $mailable = new ProductSoldOutMail([
            ...$this->eventLog->data,
            'provider_dashboard_link' => Implementation::general()->urlProviderDashboard(),
        ], $emailFrom);

        $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
    }

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
