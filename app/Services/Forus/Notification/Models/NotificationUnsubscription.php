<?php

namespace App\Services\Forus\Notification\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Services\Forus\Notification\Models\NotificationUnsubscription
 *
 * @property int $id
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationUnsubscription newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationUnsubscription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationUnsubscription query()
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationUnsubscription whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationUnsubscription whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationUnsubscription whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationUnsubscription whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class NotificationUnsubscription extends Model
{
    protected $fillable = [
        'email'
    ];
}
