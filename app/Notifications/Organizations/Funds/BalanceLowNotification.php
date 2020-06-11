<?php

namespace App\Notifications\Organizations\Funds;

use App\Services\EventLogService\Models\EventLog;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Class BalanceLowNotification
 * @package App\Notifications\Organizations\Funds
 */
class BalanceLowNotification extends BaseFundsNotification {
    protected $key = 'notifications_funds.balance_low';

    protected static $permissions = [
        'view_finances'
    ];

    public function via($notifiable): array
    {
        return ['database', MailChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage|false
     */
    public function toMail($notifiable)
    {
        if (!EventLog::find($this->eventId)) {
            return false;
        }

        $eventMeta = EventLog::find($this->eventId)->data;

        return (new MailMessage)
            ->subject(mail_trans('balance_warning.title', $eventMeta))
            ->greeting(mail_trans('dear_sponsor', $eventMeta))
            ->line(mail_trans('balance_warning.budget_reached', $eventMeta))
            ->line(mail_trans('balance_warning.budget_left_fund', $eventMeta))
            ->line(mail_trans('balance_warning.no_transactions', $eventMeta))
            ->line(mail_trans('balance_warning.you_can_login', $eventMeta));
    }
}
