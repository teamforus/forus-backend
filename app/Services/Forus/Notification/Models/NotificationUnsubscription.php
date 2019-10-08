<?php

namespace App\Models;

use Carbon\Carbon;

/**
 * Class NotificationUnsubscription
 * @property int $id
 * @property string $email
 * @property Carbon $created_at
 * * @property Carbon $updated_at
 * @package App\Models
 */
class NotificationUnsubscription extends Model
{
    protected $fillable = [
        'email'
    ];
}
