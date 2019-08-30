<?php

namespace App\Models;

class NotificationPreference extends Model
{
    protected $fillable = ['identity_address', 'notification_type_id', 'subscribed'];
}
