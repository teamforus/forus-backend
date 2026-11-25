<?php

namespace App\Notifications\Organizations\Funds;

use App\Mail\Funds\FundBalanceWarningMail;
use App\Models\Fund;
use App\Models\Identity;
use App\Models\Notification;
use App\Models\Permission;
use App\Services\EventLogService\Models\EventLog;

/**
 * Notify sponsor that the fund balance is low (reached the threshold).
 */
class BalanceLowNotification extends BaseFundsNotification
{
    protected static ?string $key = 'notifications_funds.balance_low';
    protected static string|array $permissions = Permission::VIEW_FINANCES;

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var Fund $fund */
        $fund = $this->eventLog->loggable;
        $mailable = new FundBalanceWarningMail($this->eventLog->data, $fund->getEmailFrom());

        $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
    }

    /**
     * @param Notification $notification
     * @param EventLog $event
     * @return array|null
     */
    public static function getDashboardRoute(Notification $notification, EventLog $event): ?array
    {
        return static::makeDashboardRoute('funds-show', [
            'organizationId' => $notification->organization_id,
            'fundId' => $event->data['fund_id'] ?? null,
        ]);
    }
}
