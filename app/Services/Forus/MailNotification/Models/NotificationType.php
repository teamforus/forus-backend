<?php

namespace App\Services\Forus\MailNotification\Models;

use App\Models\Model;
use App\Models\NotificationPreference;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationType extends Model
{
    protected $fillable = ['notification_type'];

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class, 'notification_type_id', 'id');
    }
}