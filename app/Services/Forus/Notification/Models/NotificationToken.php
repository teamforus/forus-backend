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
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationToken newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationToken query()
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationToken whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationToken whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationToken whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationToken whereUpdatedAt($value)
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
            case self::TYPE_PUSH_IOS: $notification = new ApnBasicNotification($title, $body); break;
            case self::TYPE_PUSH_ANDROID: $notification = new FcmBasicNotification($title, $body); break;
            default: return false;
        }

        return $notification->onQueue(env('NOTIFICATIONS_QUEUE_NAME', 'push_notifications'));
    }
}
