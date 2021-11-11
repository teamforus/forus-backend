<?php /** @noinspection PhpUnused */


namespace App\Notifications;


use App\Notifications\Identities\BaseIdentityNotification;
use Illuminate\Notifications\Notification;

class MailChannel extends \Illuminate\Notifications\Channels\MailChannel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification|BaseIdentityNotification  $notification
     * @return void
     * @noinspection PhpUnused
     */
    public function send($notifiable, Notification $notification): void
    {
        $notification->toMail($notifiable);
    }
}