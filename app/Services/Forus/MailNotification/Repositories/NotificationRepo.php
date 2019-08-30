<?php

namespace App\Services\Forus\MailNotification\Repositories;

use App\Models\NotificationPreference;
use App\Services\Forus\MailNotification\Interfaces\INotificationRepo;
use App\Services\Forus\MailNotification\Models\NotificationType;

class NotificationRepo implements INotificationRepo
{
    protected $model;

    public function __construct(NotificationPreference $model)
    {
        $this->model = $model;
    }

    public function unsubscribeForIdentity(string $identity_address): void
    {
        $types = NotificationType::query()->pluck('id');

        foreach ($types as $type_id) {
            $preference = $this->model->newQuery()->updateOrCreate([
                'identity_address' => $identity_address,
                'notification_type_id' => $type_id,
                'subscribed' => false
            ]);
        }
    }
}