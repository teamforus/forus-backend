<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\SystemNotificationConfig
 *
 * @property int $id
 * @property int $system_notification_id
 * @property int $implementation_id
 * @property bool $enable_all
 * @property bool $enable_mail
 * @property bool $enable_push
 * @property bool $enable_database
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Implementation $implementation
 * @property-read \App\Models\SystemNotification $system_notification
 * @method static \Illuminate\Database\Eloquent\Builder|SystemNotificationConfig newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SystemNotificationConfig newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SystemNotificationConfig query()
 * @method static \Illuminate\Database\Eloquent\Builder|SystemNotificationConfig whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemNotificationConfig whereEnableAll($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemNotificationConfig whereEnableDatabase($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemNotificationConfig whereEnableMail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemNotificationConfig whereEnablePush($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemNotificationConfig whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemNotificationConfig whereImplementationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemNotificationConfig whereSystemNotificationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemNotificationConfig whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class SystemNotificationConfig extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'system_notification_id', 'implementation_id',
        'enable_all', 'enable_mail', 'enable_push', 'enable_database',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'enable_all' => 'boolean',
        'enable_mail' => 'boolean',
        'enable_push' => 'boolean',
        'enable_database' => 'boolean',
    ];

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function implementation(): BelongsTo
    {
        return $this->belongsTo(Implementation::class);
    }

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function system_notification(): BelongsTo
    {
        return $this->belongsTo(SystemNotification::class);
    }
}
