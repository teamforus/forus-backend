<?php

namespace App\Services\Forus\MailNotification\Repositories;

use App\Models\NotificationPreference;
use App\Services\Forus\MailNotification\Interfaces\INotificationRepo;
use App\Services\Forus\MailNotification\Models\NotificationType;
use Illuminate\Support\Collection;

class NotificationRepo implements INotificationRepo
{
    protected $model;

    public function __construct(NotificationPreference $model)
    {
        $this->model = $model;
    }

    public function unsubscribeForIdentity(string $identityAddress): void
    {
        $types = NotificationType::query()->pluck('id');

        foreach ($types as $typeId) {
            $this->model->newQuery()->updateOrCreate([
                'identity_address' => $identityAddress,
                'notification_type_id' => $typeId,
            ], [
                'subscribed' => false
            ]);
        }
    }

    public function getNotificationPreferences(string $identityAddress): Collection
    {
        return NotificationType::with(['notificationPreferences' => function ($query) use ($identityAddress) {
            $query->where('notification_preferences.identity_address', '=', $identityAddress);
        }])->get();
    }

    public function updateForIdentity(
        string $identityAddress,
        array $data
    ): void {
        $types = NotificationType::query()
            ->whereIn('notification_type', array_keys($data))
            ->select('id', 'notification_type')
            ->get();

        foreach ($types as $type) {
            $this->model->newQuery()->updateOrCreate([
                'identity_address' => $identityAddress,
                'notification_type_id' => $type->id,
            ], [
                'subscribed' => $data[$type->notification_type]
            ]);
        }
    }
}