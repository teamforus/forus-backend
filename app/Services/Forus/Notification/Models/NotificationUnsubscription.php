<?php

namespace App\Models;

/**
 * App\Models\NotificationUnsubscription
 *
 * @property int $id
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationUnsubscription newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationUnsubscription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationUnsubscription query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationUnsubscription whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationUnsubscription whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationUnsubscription whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationUnsubscription whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class NotificationUnsubscription extends Model
{
    protected $fillable = [
        'email'
    ];
}
