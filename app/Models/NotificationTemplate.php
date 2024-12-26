<?php

namespace App\Models;

use App\Helpers\Markdown;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use League\CommonMark\Exception\CommonMarkException;

/**
 * App\Models\NotificationTemplate
 *
 * @property int $id
 * @property string $type
 * @property bool $formal
 * @property int $system_notification_id
 * @property int $implementation_id
 * @property int|null $fund_id
 * @property string $title
 * @property string $content
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund|null $fund
 * @property-read \App\Models\Implementation $implementation
 * @property-read \App\Models\Implementation $system_notification
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplate whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplate whereFormal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplate whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplate whereImplementationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplate whereSystemNotificationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplate whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplate whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplate whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class NotificationTemplate extends Model
{
    protected $fillable = [
        'key', 'type', 'formal', 'title', 'content', 'implementation_id', 'fund_id',
    ];

    protected $casts = [
        'formal' => 'boolean',
    ];

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

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
        return $this->belongsTo(Implementation::class);
    }

    /**
     * @return string|null
     * @throws CommonMarkException
     */
    public function convertToHtml(): ?string
    {
        return Markdown::convert($this->content ?: '');
    }
}
