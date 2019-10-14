<?php

namespace App\Models;

/**
 * App\Models\NotificationPreference
 *
 * @property int $id
 * @property string $identity_address
 * @property string $mail_key
 * @property int $subscribed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationPreference newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationPreference newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationPreference query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationPreference whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationPreference whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationPreference whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationPreference whereMailKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationPreference whereSubscribed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationPreference whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class NotificationPreference extends Model
{
    protected $fillable = [
        'identity_address',
        'mail_key',
        'subscribed'
    ];
}
