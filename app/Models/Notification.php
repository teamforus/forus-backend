<?php

namespace App\Models;

use App\Http\Requests\BaseFormRequest;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Notifications\DatabaseNotification;

/**
 * App\Models\Notification
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
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $notifiable
 * @property-read \App\Models\SystemNotification|null $system_notification
 * @method static \Illuminate\Notifications\DatabaseNotificationCollection|static[] all($columns = ['*'])
 * @method static \Illuminate\Notifications\DatabaseNotificationCollection|static[] get($columns = ['*'])
 * @method static Builder|Notification newModelQuery()
 * @method static Builder|Notification newQuery()
 * @method static Builder|Notification query()
 * @method static Builder|DatabaseNotification read()
 * @method static Builder|DatabaseNotification unread()
 * @method static Builder|Notification whereCreatedAt($value)
 * @method static Builder|Notification whereData($value)
 * @method static Builder|Notification whereEventId($value)
 * @method static Builder|Notification whereId($value)
 * @method static Builder|Notification whereKey($value)
 * @method static Builder|Notification whereNotifiableId($value)
 * @method static Builder|Notification whereNotifiableType($value)
 * @method static Builder|Notification whereOrganizationId($value)
 * @method static Builder|Notification whereReadAt($value)
 * @method static Builder|Notification whereScope($value)
 * @method static Builder|Notification whereType($value)
 * @method static Builder|Notification whereUpdatedAt($value)
 * @mixin \Eloquent
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
     * @param BaseFormRequest $request
     * @param bool|null $seen
     * @param null $query
     * @return Builder|Relation
     */
    public static function search(BaseFormRequest $request, ?bool $seen, $query = null): Builder|Relation
    {
        $query = $query ?: self::query();
        $scope = $request->client_type();

        $query->where('scope', $scope);

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->get('organization_id'));
        }

        if ($seen === true) {
            $query->whereNotNull('read_at');
        } elseif ($seen === false) {
            $query->whereNull('read_at');
        }

        return $query;
    }

    /**
     * @param BaseFormRequest $request
     * @param Identity $identity
     * @return int
     */
    public static function totalUnseenFromRequest(BaseFormRequest $request, Identity $identity): int
    {
        return self::search($request, false, $identity->notifications()->getQuery())->count();
    }
}
