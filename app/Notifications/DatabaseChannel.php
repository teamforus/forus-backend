<?php

namespace App\Notifications;

use Illuminate\Notifications\Channels\DatabaseChannel as IlluminateDatabaseChannel;
use Illuminate\Notifications\Notification;
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
    protected function buildPayload($notifiable, Notification $notification): array
    {
        $data = $this->getData($notifiable, $notification);

        return [
            'id' => $notification->id,
            'type' => method_exists($notification, 'databaseType')
                ? $notification->databaseType($notifiable)
                : get_class($notification),
            'data' => $data,
            'read_at' => null,
            ...Arr::only($data, ['scope', 'key', 'event_id', 'organization_id']),
        ];
    }
}
