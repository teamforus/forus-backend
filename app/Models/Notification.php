<?php

namespace App\Models;

use App\Http\Requests\Api\Platform\Notifications\IndexNotificationsRequest;
use App\Searches\NotificationSearch;
use App\Services\EventLogService\Models\EventLog;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\DatabaseNotification;

/**
 * App\Models\Notification.
 *
 * @property string $id
 * @property string|null $key
 * @property string|null $scope
 * @property int|null $organization_id
 * @property int|null $event_id
 * @property string $type
 * @property string $notifiable_type
 * @property int $notifiable_id
 * @property array $data
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read EventLog|null $event
 * @property-read \Illuminate\Database\Eloquent\Model|Eloquent $notifiable
 * @property-read \App\Models\SystemNotification|null $system_notification
 * @method static \Illuminate\Notifications\DatabaseNotificationCollection|static[] all($columns = ['*'])
 * @method static \Illuminate\Notifications\DatabaseNotificationCollection|static[] get($columns = ['*'])
 * @method static Builder<static>|Notification newModelQuery()
 * @method static Builder<static>|Notification newQuery()
 * @method static Builder<static>|Notification query()
 * @method static Builder<static>|Notification read()
 * @method static Builder<static>|Notification unread()
 * @method static Builder<static>|Notification whereCreatedAt($value)
 * @method static Builder<static>|Notification whereData($value)
 * @method static Builder<static>|Notification whereEventId($value)
 * @method static Builder<static>|Notification whereId($value)
 * @method static Builder<static>|Notification whereKey($value)
 * @method static Builder<static>|Notification whereNotifiableId($value)
 * @method static Builder<static>|Notification whereNotifiableType($value)
 * @method static Builder<static>|Notification whereOrganizationId($value)
 * @method static Builder<static>|Notification whereReadAt($value)
 * @method static Builder<static>|Notification whereScope($value)
 * @method static Builder<static>|Notification whereType($value)
 * @method static Builder<static>|Notification whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Notification extends DatabaseNotification
{
    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function system_notification(): BelongsTo
    {
        return $this->belongsTo(SystemNotification::class, 'key', 'key');
    }

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(EventLog::class);
    }

    /**
     * @param IndexNotificationsRequest $request
     * @param Identity $identity
     * @return int
     */
    public static function totalUnseenFromRequest(IndexNotificationsRequest $request, Identity $identity): int
    {
        $search = new NotificationSearch([
            ...$request->only('organization_id'),
            'seen' => false,
        ], $identity->notifications()->where('scope', $request->client_type()));

        return $search->query()->count();
    }
}
