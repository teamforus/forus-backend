<?php


namespace App\Notifications;


use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Channels\DatabaseChannel as IlluminateDatabaseChannel;
use Illuminate\Support\Arr;

class DatabaseChannel extends IlluminateDatabaseChannel
{
    /**
     * Build an array payload for the DatabaseNotification Model.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array
     */
    protected function buildPayload($notifiable, Notification $notification)
    {
        $data = $this->getData($notifiable, $notification);
        $fillable = ['scope', 'key', 'event_id', 'organization_id'];

        return [
            'id' => $notification->id,
            'type' => method_exists($notification, 'databaseType')
                ? $notification->databaseType($notifiable)
                : get_class($notification),
            'data' => Arr::except($data, $fillable),
            'read_at' => null,
            ...Arr::only($data, $fillable),
        ];
    }
}