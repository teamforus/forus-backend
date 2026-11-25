<?php

namespace App\Notifications\Organizations\Funds;

use App\Mail\Funds\ProviderAppliedMail;
use App\Models\Fund;
use App\Models\Identity;
use App\Models\Notification;
use App\Models\Permission;
use App\Services\EventLogService\Models\EventLog;

/**
 * Notify sponsor that a new provider applied to the fund.
 */
class FundProviderAppliedNotification extends BaseFundsNotification
{
    protected static ?string $key = 'notifications_funds.provider_applied';
    protected static string|array $permissions = Permission::MANAGE_PROVIDERS;

    /**
     * Get the mail representation of the notification.
     *
     * @param Identity $identity
     * @return void
     */
    public function toMail(Identity $identity): void
    {
        /** @var Fund $fund */
        $fund = $this->eventLog->loggable;

        $mailable = new ProviderAppliedMail([
            ...$this->eventLog->data,
            'sponsor_dashboard_link' => $fund->urlSponsorDashboard(),
        ], $fund->fund_config->implementation->getEmailFrom());

        $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
    }

    /**
     * @param Notification $notification
     * @param EventLog $event
     * @return array|null
     */
    public static function getDashboardRoute(Notification $notification, EventLog $event): ?array
    {
        return static::makeDashboardRoute('fund-provider', [
            'organizationId' => $notification->organization_id,
            'fundId' => $event->data['fund_id'] ?? null,
            'id' => $event->data['fund_provider_id'] ?? null,
        ]);
    }
}
