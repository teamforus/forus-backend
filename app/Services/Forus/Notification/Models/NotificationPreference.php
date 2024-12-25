<?php

namespace App\Services\Forus\Notification\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Services\Forus\Notification\Models\NotificationPreference
 *
 * @property int $id
 * @property string $identity_address
 * @property string $key
 * @property string $type
 * @property int $subscribed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereSubscribed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationPreference whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class NotificationPreference extends Model
{
    protected $fillable = [
        'identity_address', 'key', 'subscribed', 'type'
    ];
}
