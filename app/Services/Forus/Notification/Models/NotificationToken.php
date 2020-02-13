<?php

namespace App\Services\Forus\Notification\Models;

use App\Services\Forus\Notification\Messages\ApnBasicNotification;
use App\Services\Forus\Notification\Messages\FcmBasicNotification;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Services\Forus\Notification\Models\NotificationToken
 *
 * @property int $id
 * @property string $identity_address
 * @property string $type
 * @property string $token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Notification\Models\NotificationToken newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Notification\Models\NotificationToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Notification\Models\NotificationToken query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Notification\Models\NotificationToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Notification\Models\NotificationToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Notification\Models\NotificationToken whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Notification\Models\NotificationToken whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Notification\Models\NotificationToken whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Notification\Models\NotificationToken whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class NotificationToken extends Model
{
    const TYPE_PUSH_IOS = 'apn';
    const TYPE_PUSH_ANDROID = 'fcm';

    const TYPES = [
        self::TYPE_PUSH_IOS,
        self::TYPE_PUSH_ANDROID,
    ];

    protected $fillable = [
        'id', 'identity_address', 'type', 'token'
    ];

    /**
     * @param string $title
     * @param string $body
     * @return ApnBasicNotification|FcmBasicNotification|bool
     */
    public function makeBasicNotification(string $title, string $body)
    {
        switch ($this->type) {
            case self::TYPE_PUSH_IOS: {
                return (new ApnBasicNotification($title, $body))->onQueue(
                    env('NOTIFICATIONS_QUEUE_NAME', 'push_notifications')
                );
            } break;
            case self::TYPE_PUSH_ANDROID: {
                return (new FcmBasicNotification($title, $body))->onQueue(
                    env('NOTIFICATIONS_QUEUE_NAME', 'push_notifications')
                );
            } break;
        }

        return false;
    }
}
