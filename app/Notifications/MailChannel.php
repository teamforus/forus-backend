<?php


namespace App\Notifications;


use App\Models\Identity;
use App\Notifications\Identities\BaseIdentityNotification;
use Illuminate\Notifications\Notification;

class MailChannel extends \Illuminate\Notifications\Channels\MailChannel
{
    /**
     * Send the given notification.
     *
     * @param Identity $notifiable
     * @param BaseIdentityNotification $notification
     * @return void
     * @noinspection PhpUnused
     */
    public function send($notifiable, Notification $notification): void
    {
        if ($notifiable->email) {
            $notification->toMail($notifiable);
        }
    }
}