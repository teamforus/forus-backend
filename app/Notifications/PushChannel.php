<?php

namespace App\Notifications;

use App\Notifications\Identities\BaseIdentityNotification;
use Illuminate\Notifications\Notification;

class PushChannel extends \Illuminate\Notifications\Channels\MailChannel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification|BaseIdentityNotification  $notification
     * @return void
     * @noinspection PhpUnused
     */
    public function send($notifiable, Notification|BaseIdentityNotificatioN $notification): void
    {
        $notification->toPush($notifiable);
    }
}
