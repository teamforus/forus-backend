<?php

namespace App\Models;

use App\Http\Requests\BaseFormRequest;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;

/**
 * App\Models\Notification
 *
 * @property string $id
 * @property string $type
 * @property string $notifiable_type
 * @property int $notifiable_id
 * @property array $data
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $notifiable
 * @method static \Illuminate\Notifications\DatabaseNotificationCollection|static[] all($columns = ['*'])
 * @method static \Illuminate\Notifications\DatabaseNotificationCollection|static[] get($columns = ['*'])
 * @method static Builder|Notification newModelQuery()
 * @method static Builder|Notification newQuery()
 * @method static Builder|Notification query()
 * @method static Builder|DatabaseNotification read()
 * @method static Builder|DatabaseNotification unread()
 * @method static Builder|Notification whereCreatedAt($value)
 * @method static Builder|Notification whereData($value)
 * @method static Builder|Notification whereId($value)
 * @method static Builder|Notification whereNotifiableId($value)
 * @method static Builder|Notification whereNotifiableType($value)
 * @method static Builder|Notification whereReadAt($value)
 * @method static Builder|Notification whereType($value)
 * @method static Builder|Notification whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Notification extends DatabaseNotification
{
    /**
     * @param BaseFormRequest $request
     * @param bool|null $seen
     * @param null $query
     * @return Builder
     */
    public static function search(BaseFormRequest $request, ?bool $seen, $query = null): Builder
    {
        $query = $query ?: self::query();
        $scope = $request->client_type();

        $query->where(static function(Builder $builder) use ($scope) {
            $builder->where('data->scope', $scope);
            $builder->orWhereJsonContains('data->scope', $scope);
        });

        if ($request->has('organization_id')) {
            $query->where('data->organization_id', $request->get('organization_id'));
        }

        if ($seen === true) {
            $query->whereNotNull('read_at');
        } elseif ($seen === false) {
            $query->whereNull('read_at');
        }

        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    /**
     * @param BaseFormRequest $request
     * @param Identity $identity
     * @return LengthAwarePaginator
     */
    public static function paginateFromRequest(
        BaseFormRequest $request,
        Identity $identity
    ): LengthAwarePaginator {
        $seen = $request->input('seen');
        $per_page = $request->input('per_page', 15);

        $notifications = self::search($request, $seen, $identity->notifications()->getQuery())->paginate($per_page);

        if ($request->input('mark_read', false)) {
            self::whereKey(array_pluck($notifications->items(), 'id'))->whereNull('read_at')->update([
                'read_at' => now()
            ]);
        }

        return $notifications;
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

    /**
     * @return EventLog|null
     */
    public function findEventLog(): ?EventLog
    {
        return EventLog::find($this->data['event_id'] ?? null);
    }
}
