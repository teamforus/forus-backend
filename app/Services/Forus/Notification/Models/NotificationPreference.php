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
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationPreference newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationPreference newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationPreference query()
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationPreference whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationPreference whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationPreference whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationPreference whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationPreference whereSubscribed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationPreference whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationPreference whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class NotificationPreference extends Model
{
    protected $fillable = [
        'identity_address', 'key', 'subscribed', 'type'
    ];
}
