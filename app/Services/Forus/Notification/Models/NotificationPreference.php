<?php

namespace App\Models;

/**
 * Class NotificationPreference
 * @property int $id
 * @property string $identity_address
 * @property string $mail_key
 * @package App\Models
 */
class NotificationPreference extends Model
{
    protected $fillable = [
        'identity_address',
        'mail_key',
        'subscribed'
    ];
}
