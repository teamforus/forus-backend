<?php

namespace App\Models;

use App\Services\Forus\Identity\Models\Identity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
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
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Notification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Notification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Notification query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Notification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Notification whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Notification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Notification whereNotifiableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Notification whereNotifiableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Notification whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Notification whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Notification whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Notification extends DatabaseNotification
{
    /**
     * @param Request $request
     * @param string $scope
     * @param bool|null $seen
     * @param null $query
     * @return Builder
     */
    public static function search(
        Request $request,
        string $scope,
        ?bool $seen,
        $query = null
    ): Builder {
        $query = $query ?: self::query();

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
     * @param Request $request
     * @param Identity $identity
     * @return LengthAwarePaginator
     */
    public static function paginateFromRequest(
        Request $request, Identity $identity
    ): LengthAwarePaginator {
        $per_page = $request->input('per_page', 15);
        $seen = $request->input('seen');

        $notifications = self::search(
            $request, client_type(), $seen, $identity->notifications()->getQuery()
        )->paginate($per_page);

        if ((bool) $request->input('mark_read', false)) {
            self::whereKey(
                array_pluck($notifications->items(), 'id')
            )->whereNull('read_at')->update([
                'read_at' => now()
            ]);
        }

        return $notifications;
    }

    /**
     * @param Request $request
     * @param Identity $identity
     * @return int
     */
    public static function totalUnseenFromRequest(
        Request $request, Identity $identity
    ): int {
        return self::search(
            $request, client_type(), false, $identity->notifications()->getQuery()
        )->count();
    }
}
