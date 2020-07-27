<?php


namespace App\Notifications;


use Illuminate\Notifications\Notification;

class MailChannel extends \Illuminate\Notifications\Channels\MailChannel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification): void
    {
        $notification->toMail($notifiable);
    }
}